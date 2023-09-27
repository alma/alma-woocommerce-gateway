<?php
/**
 * Alma_Order_Helper.
 *
 * @since 4.?
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Alma_Checkout;
use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Exceptions\Alma_Api_Create_Payments_Exception;
use Alma\Woocommerce\Exceptions\Alma_Build_Order_Exception;
use Alma\Woocommerce\Exceptions\Alma_Create_Payments_Exception;
use Alma\Woocommerce\Exceptions\Alma_Exception;
use Alma\Woocommerce\Exceptions\Alma_No_Order_Exception;
use Alma\Woocommerce\Exceptions\Alma_Plans_Definition_Exception;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Standard;

/**
 * Class Alma_Order_Helper.
 */
class Alma_Order_Helper {

	const SHOP_ORDER = 'shop_order';

	const WC_PROCESSING = 'wc-processing';

	const WC_COMPLETED = 'wc-completed';


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Alma_Logger();
	}


	/**
	 * The status for complete orders.
	 *
	 * @var array The status order completed.
	 */
	protected static $status_order_completed = array(
		self::WC_PROCESSING,
		self::WC_COMPLETED,
	);

	/**
	 * Gets the WC orders in a date range.
	 *
	 * @param string $from The date from.
	 * @param string $to The date to.
	 *
	 * @return \WC_Order[]
	 */
	public function get_orders_by_date_range( $from, $to ) {
		return wc_get_orders(
			array(
				'date_created' => $from . '...' . $to,
				'type'         => self::SHOP_ORDER,
				'status'       => self::$status_order_completed,
			)
		);
	}

	/**
	 * Gets the WC orders by customer id with limit.
	 *
	 * @param int $customer_id The customer id.
	 * @param int $limit The limit.
	 *
	 * @return \WC_Order[]
	 */
	public function get_orders_by_customer_id( $customer_id, $limit = 10 ) {
		return wc_get_orders(
			array(
				'customer_id' => $customer_id,
				'limit'       => $limit,
				'type'        => self::SHOP_ORDER,
				'status'      => self::$status_order_completed,
			)
		);
	}

	/**
	 * Get merchant order url.
	 *
	 * @param \WC_Order $wc_order The WC order.
	 * @return string
	 */
	public function get_merchant_url( $wc_order ) {
		$admin_path = 'post.php?post=' . $wc_order->get_id() . '&action=edit';

		if ( version_compare( wc()->version, '3.3.0', '<' ) ) {
			return get_admin_url( null, $admin_path );
		}

		return $wc_order->get_edit_order_url();
	}

	/**
	 * Get shipping address.
	 *
	 * @param \WC_Order $wc_order The order.
	 *
	 * @return array
	 */
	public function get_shipping_address( $wc_order ) {
		if ( ! $wc_order->has_shipping_address() ) {
			return array();
		}

		return array(
			'first_name'          => $wc_order->get_shipping_first_name(),
			'last_name'           => $wc_order->get_shipping_last_name(),
			'company'             => $wc_order->get_shipping_company(),
			'line1'               => $wc_order->get_shipping_address_1(),
			'line2'               => $wc_order->get_shipping_address_2(),
			'postal_code'         => $wc_order->get_shipping_postcode(),
			'city'                => $wc_order->get_shipping_city(),
			'country_sublocality' => null,
			'state_province'      => $wc_order->get_shipping_state(),
			'country'             => $wc_order->get_shipping_country(),
		);
	}


	/**
	 * Get billing address.
	 *
	 * @param \WC_Order $wc_order The order.
	 * @return array
	 */
	public function get_billing_address( $wc_order ) {
		if ( ! $wc_order->has_billing_address() ) {
			return array();
		}

		return array(
			'first_name'          => $wc_order->get_billing_first_name(),
			'last_name'           => $wc_order->get_billing_last_name(),
			'company'             => $wc_order->get_billing_company(),
			'line1'               => $wc_order->get_billing_address_1(),
			'line2'               => $wc_order->get_billing_address_2(),
			'postal_code'         => $wc_order->get_billing_postcode(),
			'city'                => $wc_order->get_billing_city(),
			'country'             => $wc_order->get_billing_country(),
			'country_sublocality' => null,
			'state_province'      => $wc_order->get_billing_state(),
			'email'               => $wc_order->get_billing_email(),
			'phone'               => $wc_order->get_billing_phone(),
		);
	}

	/**
	 * Are we a business company.
	 *
	 * @param \WC_Order $wc_order The wc_order.
	 *
	 * @return bool
	 */
	public function is_business( $wc_order ) {
		if ( $wc_order->get_billing_company() ) {
			return true;
		}

		return false;
	}

	/**
	 * Payment complete.
	 *
	 * @param \WC_Order $wc_order The WC_order.
	 * @param string    $payment_id Payment Id.
	 *
	 * @return void
	 */
	public function payment_complete( $wc_order, $payment_id ) {
		$wc_order->payment_complete( $payment_id );
		wc()->cart->empty_cart();
	}

	/**
	 * Get the order.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 * @param string $payment_id The payment id.
	 *
	 * @return bool|\WC_Order|\WC_Refund
	 * @throws Alma_Build_Order_Exception Error on building order.
	 */
	public function get_order( $order_id, $order_key = null, $payment_id = null ) {
		$wc_order = wc_get_order( $order_id );

		if (
			! $wc_order
			&& $order_key
		) {
			// We have an invalid $order_id, probably because invoice_prefix has changed.
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$wc_order = wc_get_order( $order_id );
		}

		if (
			! $wc_order
			|| (
				$order_key
				&& $wc_order->get_order_key() !== $order_key
			)
		) {
			throw new Alma_Build_Order_Exception( $order_id, $order_key, $payment_id );
		}

		return $wc_order;
	}

	/**
	 *  Create the order and the payment id for In page.
	 *
	 * @throws Alma_Create_Payments_Exception Exception.
	 * @return void
	 */
	public function alma_do_checkout_in_page() {
		$order = null;

		// The nonce verification is done in   is_alma_payment_method.
		try {
			if (
				isset( $_POST['fields'] ) // phpcs:ignore WordPress.Security.NonceVerification
				&& ! empty( $_POST['fields'] ) // phpcs:ignore WordPress.Security.NonceVerification
			) {

				list($payment_id, $order_id) = $this->create_inpage_order( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

				wp_send_json_success(
					array(
						'payment_id' => $payment_id,
						'order_id'   => $order_id,
					)
				);
			}

			throw new Alma_Create_Payments_Exception();

		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );

			if ( $order ) {
				$order->update_status( 'failed', $e->getMessage() );
			}

			wc_add_notice( __( 'There was an error creating your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' ), Alma_Constants_Helper::ERROR );

			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Create the order for in page.
	 *
	 * @param array $post_fields The data to process.
	 *
	 * @return array
	 * @throws Alma_Api_Create_Payments_Exception Exception.
	 * @throws Alma_Plans_Definition_Exception  Exception.
	 * @throws \WC_Data_Exception  Exception.
	 */
	protected function create_inpage_order( $post_fields ) {
		$alma_checkout = new Alma_Checkout();
		$order         = $alma_checkout->process_checkout();

		// We ignore the nonce verification because process_payment is called after validate_fields.
		$settings       = new Alma_Settings();
		$payment_helper = new Alma_Payment_Helper();

		$fee_plan = $settings->build_fee_plan( $post_fields[ Alma_Constants_Helper::ALMA_FEE_PLAN_IN_PAGE ] );

		$payment = $payment_helper->create_payments( $order, $fee_plan, true );

		return array(
			$payment->id,
			$order->get_id(),
		);
	}

	/**
	 * Get the title of the Alma Gateway.
	 *
	 * @param string $id The alma gateway type id.
	 * @return string
	 * @throws Alma_Exception Exception.
	 */
	public function get_alma_gateway_title( $id ) {
		$settings = new Alma_Settings();
		if ( in_array( $id, Alma_Constants_Helper::$gateways_ids, true ) ) {
			return $settings->get_title( $id );
		}

		throw new Alma_Exception( sprintf( 'Unknown gateway id : %s', $id ) );
	}

	/**
	 * Abandonment by the client.
	 *
	 * @return void
	 */
	public function alma_cancel_order_in_page() {
		try {
			$order_id     = sanitize_text_field( $_POST['order_id'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$order_helper = new Alma_Order_Helper();
			$order        = $order_helper->get_order( $order_id );
			$order->update_status( 'cancelled', __( 'Abandonment by the client.', 'alma-gateway-for-woocommerce' ) );
			wp_send_json_success();
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage() );
			wp_send_json_error( $e->getMessage(), 500 );

		}
	}

	/**
	 * Handle customer return.
	 *
	 * @return void|null
	 */
	public function handle_customer_return() {
		$payment_helper = new Alma_Payment_Helper();
		$wc_order       = $payment_helper->handle_customer_return();

		// Redirect user to the order confirmation page.
		$alma_gateway = new Alma_Payment_Gateway_Standard();

		$return_url = $alma_gateway->get_return_url( $wc_order );

		wp_safe_redirect( $return_url );
		exit();
	}

	/**
	 * Handle ipn callback.
	 *
	 * @return void
	 */
	public function handle_ipn_callback() {
		$payment_helper = new Alma_Payment_Helper();
		$payment_helper->handle_ipn_callback();
	}
}
