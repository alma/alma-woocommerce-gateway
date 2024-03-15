<?php
/**
 * PaymentHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\DependenciesError;
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Payment;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Exceptions\AmountMismatchException;
use Alma\Woocommerce\Exceptions\ApiCreatePaymentsException;
use Alma\Woocommerce\Exceptions\ApiFetchPaymentsException;
use Alma\Woocommerce\Exceptions\BuildOrderException;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Exceptions\IncorrectPaymentException;
use Alma\Woocommerce\Exceptions\PlansDefinitionException;
use Alma\Woocommerce\Services\PaymentUponTriggerService;
use Alma\Woocommerce\AlmaSettings;

/**
 * PaymentHelper.
 */
class PaymentHelper {


	/**
	 * The logger.
	 *
	 * @var AlmaLogger
	 */
	protected $logger;


	/**
	 * The settings.
	 *
	 * @var SettingsHelper
	 */
	protected $alma_settings;

	/**
	 * The tool helper.
	 *
	 * @var ToolsHelper
	 */
	protected $tool_helper;

	/**
	 * The alma order helper
	 *
	 * @var OrderHelper
	 */
	protected $order_helper;

	/**
	 * Payment upon trigger.
	 *
	 * @var PaymentUponTriggerService
	 */
	protected $payment_upon_trigger;

	/**
	 * The cart helper.
	 *
	 * @var CartHelper
	 */
	protected $cart_helper;


	/**
	 * Contructor.
	 */
	public function __construct() {
		$this->logger               = new AlmaLogger();
		$this->payment_upon_trigger = new PaymentUponTriggerService();
		$this->alma_settings        = new AlmaSettings();
		$this->tool_helper          = new ToolsHelper();
		$this->cart_helper          = new CartHelper();
		$this->order_helper         = new OrderHelper();
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
	 *
	 * @return string|void
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
			$error_msg = __( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );
			wc_add_notice(
				$error_msg,
				ConstantsHelper::ERROR
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
		$code   = 200;
		$result = array( ConstantsHelper::SUCCESS => true );

		try {
			$this->validate_payment( $payment_id );
		} catch ( IncorrectPaymentException $e ) {
			$result = $this->manage_payment_errors( $e, $payment_id );
		} catch ( AmountMismatchException $e ) {
			$result = $this->manage_payment_errors( $e, $payment_id );
		} catch ( \Exception $e ) {
			$code   = 500;
			$result = $this->manage_payment_errors( $e, $payment_id );
		} finally {
			wp_send_json( $result, $code );
		}
	}

	/**
	 * Manage the exceptions.
	 *
	 * @param IncorrectPaymentException|AmountMismatchException|\Exception $exception The exception.
	 * @param int                                                          $payment_id The payment id.
	 *
	 * @return array
	 */
	protected function manage_payment_errors( $exception, $payment_id ) {
		$message = sprintf( ' %s - Payment id : "%s"', $exception->getMessage(), $payment_id );
		$result  = array( ConstantsHelper::ERROR => $message );
		$this->logger->error( $message );

		return $result;
	}

	/**
	 * Validate payments.
	 *
	 * @param string $payment_id The payment id.
	 *
	 * @return \WC_Order The order.
	 *
	 * @throws AmountMismatchException Amount mismatch.
	 * @throws ApiFetchPaymentsException Can't fetch payments.
	 * @throws BuildOrderException    Can't build order.
	 * @throws IncorrectPaymentException Issue with payment.
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws RequestError|AlmaException RequestError.
	 */
	public function validate_payment( $payment_id ) {
		$payment  = $this->alma_settings->fetch_payment( $payment_id );
		$wc_order = $this->order_helper->get_order( $payment->custom_data['order_id'], $payment->custom_data['order_key'], $payment_id );

		if (
			$wc_order->has_status(
				apply_filters(
					'alma_valid_order_statuses_for_payment_complete',
					ConstantsHelper::$payment_statuses
				)
			)
		) {
			$this->manage_mismatch( $wc_order, $payment, $payment_id );

			$this->manage_potential_fraud( $wc_order, $payment, $payment_id );

			// If we're down here, everything went OK, and we can validate the order!
			$this->order_helper->payment_complete( $wc_order, $payment_id );

			$this->update_order_post_meta_if_deferred_trigger( $payment, $wc_order );
		}

		return $wc_order;
	}

	/**
	 * Handle the potentials frauds.
	 *
	 * @param \WC_Order $wc_order The order.
	 * @param Payment   $payment The payment.
	 * @param int       $payment_id The payment id.
	 *
	 * @return void
	 * @throws AlmaException AlmaException.
	 * @throws IncorrectPaymentException IncorrectPaymentException.
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws RequestError RequestError.
	 */
	protected function manage_potential_fraud( $wc_order, $payment, $payment_id ) {
		if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
			$this->alma_settings->flag_as_fraud( $payment_id, Payment::FRAUD_STATE_ERROR );
			$wc_order->update_status( 'failed', Payment::FRAUD_STATE_ERROR );

			throw new IncorrectPaymentException( $payment_id, $wc_order->get_id(), $payment->state );
		}
	}

	/**
	 * Handle the mismatches cases.
	 *
	 * @param \WC_Order $wc_order The order.
	 * @param Payment   $payment The payment.
	 * @param int       $payment_id The payment id.
	 *
	 * @return void
	 * @throws AlmaException AlmaException.
	 * @throws IncorrectPaymentException IncorrectPaymentException.
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws RequestError RequestError.
	 */
	protected function manage_mismatch( $wc_order, $payment, $payment_id ) {
		$total_in_cent = $this->tool_helper->alma_price_to_cents( $wc_order->get_total() );

		if ( $total_in_cent !== $payment->purchase_amount ) {
			$this->alma_settings->flag_as_fraud( $payment_id, Payment::FRAUD_AMOUNT_MISMATCH );
			$wc_order->update_status( 'failed', Payment::FRAUD_AMOUNT_MISMATCH );

			throw new AmountMismatchException( $payment_id, $wc_order->get_id(), $total_in_cent, $payment->purchase_amount );
		}
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
			$wc_order->update_meta_data( 'alma_payment_upon_trigger_enabled', true );
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
	 * @return \WC_Order|null
	 */
	public function validate_payment_on_customer_return( $payment_id ) {
		$wc_order  = null;
		$error_msg = __( 'There was an error when validating your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );

		try {
			$wc_order = $this->validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			if ( $wc_order ) {
				$wc_order->update_status( 'failed', $e->getMessage() );
			}

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
		wc_add_notice( $error_msg, ConstantsHelper::ERROR );

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
		$cart_helper     = new CartHelper();
		$customer_helper = new CustomerHelper();
		$settings        = new AlmaSettings();

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
	 * @param \WC_Order $wc_order Order.
	 * @param FeePlan   $fee_plan Fee plan definition.
	 * @param string    $payment_type The payment type.
	 * @param boolean   $is_in_page In Page mode.
	 *
	 * @return array
	 */
	public function get_payment_payload_from_order( $wc_order, $fee_plan, $payment_type, $is_in_page = false ) {

		try {
			$wc_order->add_order_note( $payment_type );

			$data = $this->build_data_for_alma( $wc_order, $fee_plan, $is_in_page );

		} catch ( \Exception $e ) {
			$this->logger->error(
				sprintf(
					'Error getting payment info from order id %s. Message : %s',
					$wc_order->get_id(),
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
	 * @param boolean   $is_in_page In Page mode.
	 *
	 * @return array
	 */
	protected function build_data_for_alma( $wc_order, $fee_plan, $is_in_page = false ) {
		$billing_address  = $this->order_helper->get_billing_address( $wc_order );
		$shipping_address = $this->order_helper->get_shipping_address( $wc_order );

		return array(
			'payment'  => $this->build_payment_details( $wc_order, $fee_plan, $billing_address, $shipping_address, $is_in_page ),
			'order'    => $this->build_order_details( $wc_order ),
			'customer' => $this->build_customer_details( $wc_order, $billing_address, $shipping_address ),
		);
	}

	/**
	 * Build payment payload.
	 *
	 * @param \WC_Order $wc_order The WC order.
	 * @param FeePlan   $fee_plan The Fee Plan.
	 * @param array     $billing_address The Billing address.
	 * @param array     $shipping_address The Shipping address.
	 * @param boolean   $is_in_page In Page mode.
	 *
	 * @return array
	 */
	protected function build_payment_details( $wc_order, $fee_plan, $billing_address = array(), $shipping_address = array(), $is_in_page = false ) {

		$data = array(
			'purchase_amount'     => $this->tool_helper->alma_price_to_cents( $wc_order->get_total() ),
			'return_url'          => $this->tool_helper->url_for_webhook( ConstantsHelper::CUSTOMER_RETURN ),
			'ipn_callback_url'    => $this->tool_helper->url_for_webhook( ConstantsHelper::IPN_CALLBACK ),
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

		if ( $is_in_page ) {
			$data['origin'] = 'online_in_page';
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
	 * @param \WC_Order $wc_order The order .
	 * @param  FeePlan   $fee_plan The fee plan.
	 * @param boolean   $is_in_page In Page mode.
	 *
	 * @return Payment
	 *
	 * @throws ApiCreatePaymentsException Exception.
	 * @throws PlansDefinitionException Exception.
	 */
	public function create_payments( $wc_order, $fee_plan, $is_in_page = false ) {

		$payment_type = $this->get_payment_method( $fee_plan );

		$payload = $this->get_payment_payload_from_order( $wc_order, $fee_plan, $payment_type, $is_in_page );

		return $this->alma_settings->create_payment( $payload, $wc_order, $fee_plan );

	}

	/**
	 * Get the payment method fee plan title.
	 *
	 * @param FeePlan $fee_plan The fee plan.
	 * @return string
	 *
	 * @throws PlansDefinitionException Exception.
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

		throw new PlansDefinitionException();
	}

}
