<?php
/**
 * Alma_Payment_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\DependenciesError;
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Alma_Payment_Upon_Trigger;
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Exceptions\Alma_Amount_Mismatch_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Create_Payments_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Fetch_Payments_Exception;
use Alma\Woocommerce\Exceptions\Alma_Build_Order_Exception;
use Alma\Woocommerce\Exceptions\Alma_Incorrect_Payment_Exception;
use Alma\Woocommerce\Exceptions\Alma_Exception;
use Alma\Woocommerce\Exceptions\Alma_Plans_Definition_Exception;

/**
 * Alma_Payment_Helper.
 */
class Alma_Payment_Helper {


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;


	/**
	 * The settings.
	 *
	 * @var Alma_Settings_Helper
	 */
	protected $alma_settings;

	/**
	 * The tool helper.
	 *
	 * @var Alma_Tools_Helper
	 */
	protected $tool_helper;

	/**
	 * The alma order helper
	 *
	 * @var Alma_Order_Helper
	 */
	protected $order_helper;

	/**
	 * Payment upon trigger.
	 *
	 * @var Alma_Payment_Upon_Trigger
	 */
	protected $payment_upon_trigger;

	/**
	 * The cart helper.
	 *
	 * @var Alma_Cart_Helper
	 */
	protected $cart_helper;


	/**
	 * Contructor.
	 */
	public function __construct() {
		$this->logger               = new Alma_Logger();
		$this->payment_upon_trigger = new Alma_Payment_Upon_Trigger();
		$this->alma_settings        = new Alma_Settings();
		$this->tool_helper          = new Alma_Tools_Helper();
		$this->cart_helper          = new Alma_Cart_Helper();
		$this->order_helper         = new Alma_Order_Helper();
	}

	/**
	 * Handle ipn callback.
	 *
	 * @return void
	 */
	public function handle_ipn_callback() {
		$payment_id = $this->get_payment_to_validate();
		$this->validate_payment_from_ipn( $payment_id );
	}

	/**
	 * Webhooks handlers
	 *
	 * PID comes from Alma IPN callback or Alma Checkout page,
	 * it is not a user form submission: Nonce usage is not suitable here.
	 */
	protected function get_payment_to_validate() {
		$id         = sanitize_text_field( $_GET['pid'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$payment_id = isset( $id ) ? $id : null;

		if ( ! $payment_id ) {
			$this->logger->error(
				'Payment validation webhook called without a payment ID.',
				array(
					'Method' => __METHOD__,
					'PID'    => $id,
				)
			);

			wc_add_notice(
				__( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' ),
				Alma_Constants_Helper::ERROR
			);

			wp_safe_redirect( wc_get_cart_url() );
			exit();
		}

		return $payment_id;
	}

	/**
	 * Validate payment from ipn.
	 *
	 * @param string $payment_id Payment Id.
	 */
	protected function validate_payment_from_ipn( $payment_id ) {
		try {
			$this->validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			status_header( 500 );
			wp_send_json( array( Alma_Constants_Helper::ERROR => $e->getMessage() ) );
		}

		wp_send_json( array( Alma_Constants_Helper::SUCCESS => true ) );
	}

	/**
	 * Validate payments.
	 *
	 * @param string $payment_id The payment id.
	 *
	 * @return \WC_Order The order.
	 *
	 * @throws Alma_Amount_Mismatch_Exception Amount mismatch.
	 * @throws Alma_Api_Fetch_Payments_Exception Can't fetch payments.
	 * @throws Alma_Build_Order_Exception    Can't build order.
	 * @throws Alma_Incorrect_Payment_Exception Issue with payment.
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws RequestError|Alma_Exception RequestError.
	 */
	public function validate_payment( $payment_id ) {
		$payment  = $this->alma_settings->fetch_payment( $payment_id );
		$wc_order = $this->order_helper->get_order( $payment->custom_data['order_id'], $payment->custom_data['order_key'], $payment_id );

		if (
			$wc_order->has_status(
				apply_filters(
					'alma_valid_order_statuses_for_payment_complete',
					Alma_Constants_Helper::$payment_statuses
				)
			)
		) {
			$total_in_cent = $this->tool_helper->alma_price_to_cents( $wc_order->get_total() );

			if ( $total_in_cent !== $payment->purchase_amount ) {
				$this->alma_settings->flag_as_fraud( $payment_id, Payment::FRAUD_AMOUNT_MISMATCH );
				throw new Alma_Amount_Mismatch_Exception( $payment_id, $wc_order->get_id(), $total_in_cent, $payment->purchase_amount );
			}

			$first_instalment = $payment->payment_plan[0];

			if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
				$this->alma_settings->flag_as_fraud( $payment_id, Payment::FRAUD_STATE_ERROR );

				throw new Alma_Incorrect_Payment_Exception( $payment_id, $wc_order->get_id(), $payment->state, $first_instalment->state );
			}

			// If we're down here, everything went OK, and we can validate the order!
			$this->order_helper->payment_complete( $wc_order, $payment_id );

			$this->update_order_post_meta_if_deferred_trigger( $payment, $wc_order );
		}

		return $wc_order;
	}

	/**
	 * Update the order meta "alma_payment_upon_trigger_enabled" if the payment is upon trigger.
	 *
	 * @param Payment   $payment A payment.
	 * @param \WC_Order $wc_order The WC order.
	 *
	 * @return void
	 */
	public function update_order_post_meta_if_deferred_trigger( $payment, $wc_order ) {
		if ( $payment->deferred_trigger ) {
			update_post_meta( $wc_order->get_id(), 'alma_payment_upon_trigger_enabled', true );
		}
	}

	/**
	 * Handle customer return.
	 *
	 * @return \WC_Order|null
	 */
	public function handle_customer_return() {
		$payment_id = $this->get_payment_to_validate();

		return $this->validate_payment_on_customer_return( $payment_id );
	}

	/**
	 * Validate payment on customer return.
	 *
	 * @param string $payment_id Payment Id.
	 *
	 * @return \WC_Order|null
	 */
	public function validate_payment_on_customer_return( $payment_id ) {
		$wc_order  = null;
		$error_msg = __( 'There was an error when validating your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );
		try {
			$wc_order = $this->validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );
			$this->redirect_to_cart_with_error( $error_msg );
		}

		if ( ! $wc_order ) {
			$this->redirect_to_cart_with_error( $error_msg );
		}

		return $wc_order;
	}

	/**
	 * Redirect to cart with error.
	 *
	 * @param string $error_msg Error message.
	 */
	protected function redirect_to_cart_with_error( $error_msg ) {
		wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );

		$cart_url = wc_get_cart_url();
		wp_safe_redirect( $cart_url );
		exit();
	}

	/**
	 * Gets description for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 *
	 * @return string
	 */
	public function get_description( $payment_method ) {
		return $this->alma_settings->get_i18n( 'description_' . $payment_method );
	}

	/**
	 * Create Eligibility data for Alma API request from WooCommerce Cart.
	 *
	 * @return array Payload to request eligibility v2 endpoint.
	 */
	public static function get_eligibility_payload_from_cart() {
		$cart_helper     = new Alma_Cart_Helper();
		$customer_helper = new Alma_Customer_Helper();
		$settings        = new Alma_Settings();

		$data = array(
			'purchase_amount' => $cart_helper->get_total_in_cents(),
			'queries'         => $settings->get_eligible_plans_for_cart(),
			'locale'          => apply_filters( 'alma_eligibility_user_locale', get_locale() ),
		);

		$billing_country  = $customer_helper->get_billing_country();
		$shipping_country = $customer_helper->get_shipping_country();

		if ( $billing_country ) {
			$data['billing_address'] = array( 'country' => $billing_country );
		}
		if ( $shipping_country ) {
			$data['shipping_address'] = array( 'country' => $shipping_country );
		}

		return $data;
	}

	/**
	 * Create Payment data for Alma API request from WooCommerce Order.
	 *
	 * @param int     $order_id Order ID.
	 * @param FeePlan $fee_plan Fee plan definition.
	 * @param string  $payment_type The payment type.
	 *
	 * @return array
	 */
	public function get_payment_payload_from_order( $order_id, $fee_plan, $payment_type ) {

		try {
			$wc_order = $this->order_helper->get_order( $order_id );

			$wc_order->add_order_note( $payment_type );

			$data = $this->build_data_for_alma( $wc_order, $fee_plan );

		} catch ( \Exception $e ) {
			$this->logger->error(
				sprintf(
					'Error getting payment info from order id %s. Message : %s',
					$order_id,
					$e->getMessage()
				)
			);

			return array();
		}

		return apply_filters( 'alma_get_payment_payload_from_order', $data );
	}

	/**
	 * Build the data to sent to the Alma Api.
	 *
	 * @param \WC_Order $wc_order The wc order.
	 * @param FeePlan   $fee_plan Fee plan definition.
	 * @return array
	 */
	protected function build_data_for_alma( $wc_order, $fee_plan ) {
		$billing_address  = $this->order_helper->get_billing_address( $wc_order );
		$shipping_address = $this->order_helper->get_shipping_address( $wc_order );

		return array(
			'payment'                  => $this->build_payment_details( $wc_order, $fee_plan, $billing_address, $shipping_address ),
			'order'                    => $this->build_order_details( $wc_order ),
			'customer'                 => $this->build_customer_details( $wc_order, $billing_address, $shipping_address ),
			'website_customer_details' => $this->build_website_customer_details( $wc_order ),
		);
	}

	/**
	 * Build payment payload.
	 *
	 * @param \WC_Order $wc_order The WC order.
	 * @param FeePlan   $fee_plan The Fee Plan.
	 * @param array     $billing_address The Billing address.
	 * @param array     $shipping_address The Shipping address.
	 * @return array
	 */
	protected function build_payment_details( $wc_order, $fee_plan, $billing_address = array(), $shipping_address = array() ) {

		$data = array(
			'purchase_amount'     => $this->tool_helper->alma_price_to_cents( $wc_order->get_total() ),
			'return_url'          => $this->tool_helper->url_for_webhook( Alma_Constants_Helper::CUSTOMER_RETURN ),
			'ipn_callback_url'    => $this->tool_helper->url_for_webhook( Alma_Constants_Helper::IPN_CALLBACK ),
			'customer_cancel_url' => wc_get_checkout_url(),
			'installments_count'  => $fee_plan->getInstallmentsCount(),
			'deferred_days'       => $fee_plan->getDeferredDays(),
			'deferred_months'     => $fee_plan->getDeferredMonths(),
			'custom_data'         => array(
				'order_id'  => $wc_order->get_id(),
				'order_key' => $wc_order->get_order_key(),
			),
			'locale'              => apply_filters( 'alma_checkout_payment_user_locale', get_locale() ),
			'cart'                => array(
				'items' => $this->get_previous_order_items_details( $wc_order, $fee_plan, true ),
			),
			'billing_address'     => $billing_address,
			'shipping_address'    => $shipping_address,
		);

		if ( $this->payment_upon_trigger->does_payment_upon_trigger_apply_for_this_fee_plan( $fee_plan ) ) {
			$data['deferred']             = 'trigger';
			$data['deferred_description'] = $this->alma_settings->get_display_text();
		}

		return $data;
	}

	/**
	 * Build order payload.
	 *
	 * @param \WC_Order $wc_order The WC order.
	 * @return array
	 */
	protected function build_order_details( $wc_order ) {
		return array(
			'merchant_reference' => $wc_order->get_order_number(),
			'merchant_url'       => $this->order_helper->get_merchant_url( $wc_order ),
			'customer_url'       => $wc_order->get_view_order_url(),
		);
	}

	/**
	 * Build customer payload.
	 *
	 * @param \WC_Order $wc_order The WC order.
	 * @param array     $billing_address The billing address.
	 * @param array     $shipping_address The shipping address.
	 * @return array
	 */
	protected function build_customer_details( $wc_order, $billing_address = array(), $shipping_address = array() ) {
		$is_business = $this->order_helper->is_business( $wc_order );

		$data = array(
			'addresses'   => array(),
			'is_business' => $is_business,
		);

		if ( ! empty( $billing_address ) ) {
			$data['first_name']  = $billing_address['first_name'];
			$data['last_name']   = $billing_address['last_name'];
			$data['email']       = $billing_address['email'];
			$data['phone']       = $billing_address['phone'];
			$data['addresses'][] = $billing_address;

			if ( $is_business ) {
				$data['business_name'] = $wc_order->get_billing_company();
			}
		}

		if ( ! empty( $shipping_address ) ) {
			$data['addresses'][] = $shipping_address;
		}

		return $data;
	}

	/**
	 * Website Customer Details
	 *
	 * @param \WC_Order $wc_order The WC order.
	 * @return array
	 */
	protected function build_website_customer_details( $wc_order ) {
		$customer_id = $wc_order->get_customer_id();
		$is_guest    = false;

		if ( '0' == $customer_id ) {
			$is_guest = true;
		}

		return array(
			'is_guest'        => $is_guest,
			'previous_orders' => $this->get_previous_orders_details( $customer_id, $is_guest ),
		);
	}

	/**
	 * Get the previous orders details
	 *
	 * @param string  $customer_id The customer id.
	 * @param boolean $is_guest Is this a guest.
	 *
	 * @return array
	 */
	protected function get_previous_orders_details( $customer_id, $is_guest = true ) {
		if ( $is_guest ) {
			return array();
		}

		$orders = $this->order_helper->get_orders_by_customer_id( $customer_id );

		$order_details = array();

		foreach ( $orders as $wc_order ) {
			$order_details[] = $this->get_order_details( $wc_order );
		}

		return $order_details;
	}

	/**
	 * Get the order detail
	 *
	 * @param \WC_Order $wc_order The order.
	 * @return array
	 */
	protected function get_order_details( $wc_order ) {
		return array(
			'purchase_amount' => $this->tool_helper->alma_price_to_cents( $wc_order->get_total() ),
			'payment_method'  => $wc_order->get_payment_method(),
			'shipping_method' => $wc_order->get_shipping_method(),
			'created'         => $wc_order->get_date_created()->getTimestamp(),
			'items'           => $this->get_previous_order_items_details( $wc_order ),
		);
	}

	/**
	 * Retrieve the X past purchase item details.
	 *
	 * @param \WC_Order $wc_order The order.
	 * @param FeePlan   $fee_plan The Fee Plan.
	 * @param bool      $check_credit Check for payment payload if we are in credit.
	 * @return array
	 */
	protected function get_previous_order_items_details( $wc_order, $fee_plan = array(), $check_credit = false ) {
		if (
			$check_credit
			&& ! $this->alma_settings->is_pnx_plus_4( $fee_plan )
		) {
			return array();
		}

		$items = $wc_order->get_items();

		$item_details = array();

		foreach ( $items as $item ) {
			$item_details[] = $this->add_product_data( $item );
		}

		return $item_details;
	}

	/**
	 * Add details of one product.
	 *
	 * @param \WC_Order_Item $item The item order.
	 *
	 * @return array
	 */
	protected function add_product_data( $item ) {
		// @var \WC_Order_Item_Product $product_item The product.
		$product = $item->get_product();

		$categories = explode( ',', wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ',' ) ) );

		return array(
			'sku'               => $product->get_sku(),
			'title'             => $item->get_name(),
			'quantity'          => $item->get_quantity(),
			'unit_price'        => $this->tool_helper->alma_price_to_cents( $product->get_price() ),
			'line_price'        => $this->tool_helper->alma_price_to_cents( $item->get_total() ),
			'categories'        => $categories,
			'url'               => $product->get_permalink(),
			'picture_url'       => wp_get_attachment_url( $product->get_image_id() ),
			'requires_shipping' => $product->needs_shipping(),
		);
	}

	/**
	 * Call the payment api and create.
	 *
	 * @param string  $order_id The order id.
	 * @param  FeePlan $fee_plan The fee plan.
	 *
	 * @return Payment
	 * @throws Alma_Api_Create_Payments_Exception Create payment exception.
	 */
	public function create_payments( $order_id, $fee_plan ) {
		try {
			$payment_type = $this->get_payment_method( $fee_plan );

			$payload = $this->get_payment_payload_from_order( $order_id, $fee_plan, $payment_type );

			return $this->alma_settings->alma_client->payments->create( $payload );
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api create_payments, order id "%s" , Api message "%s"', $order_id, $e->getMessage() ) );
			throw new Alma_Api_Create_Payments_Exception( $order_id, $fee_plan );
		}
	}

	/**
	 * Get the payment method fee plan title.
	 *
	 * @param FeePlan $fee_plan The fee plan.
	 * @return string
	 *
	 * @throws Alma_Plans_Definition_Exception Exception.
	 */
	public function get_payment_method( $fee_plan ) {
		if ( $fee_plan->isPayNow() ) {
			return __( 'Selected payment method : Pay Now with Alma', 'alma-gateway-for-woocommerce' );
		}

		if ( $fee_plan->isPnXOnly() ) {
			// translators: %d: number of installments.
			return sprintf( __( 'Selected payment method : %d installments with Alma', 'alma-gateway-for-woocommerce' ), $fee_plan->getInstallmentsCount() );
		}

		if ( $fee_plan->isPayLaterOnly() ) {
			$deferred_months = $fee_plan->getDeferredMonths();
			$deferred_days   = $fee_plan->getDeferredDays();

			if ( $deferred_days ) {
				// translators: %d: number of deferred days.
				return sprintf( __( 'Selected payment method : D+%d deferred  with Alma', 'alma-gateway-for-woocommerce' ), $deferred_days );
			}
			if ( $deferred_months ) {
				// translators: %d: number of deferred months.
				return sprintf( __( 'Selected payment method : M+%d deferred with Alma', 'alma-gateway-for-woocommerce' ), $deferred_months );
			}
		}

		throw new Alma_Plans_Definition_Exception();
	}

}
