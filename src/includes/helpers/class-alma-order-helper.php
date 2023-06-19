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

use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Exceptions\Alma_Build_Order_Exception;
use Alma\Woocommerce\Exceptions\Alma_No_Order_Exception;

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
	private $logger;

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
}
