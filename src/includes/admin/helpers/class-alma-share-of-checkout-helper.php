<?php
/**
 * Alma_Share_Of_Checkout_Helper.
 *
 * @since 4.1.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/admin/helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Exceptions\Alma_Api_Soc_Last_Update_Dates_Exception;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Share_Of_Checkout_Helper
 */
class Alma_Share_Of_Checkout_Helper {
	/**
	 * The alma settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;

	/**
	 * The order helper.
	 *
	 * @var Alma_Order_Helper
	 */
	protected $order_helper;

	/**
	 * Helper global.
	 *
	 * @var Alma_Tools_Helper
	 */
	protected $tool_helper;


	/**
	 * Construct.
	 */
	public function __construct() {
		$this->alma_settings = new Alma_Settings();
		$this->order_helper  = new Alma_Order_Helper();
		$this->tool_helper   = new Alma_Tools_Helper();
	}


	/**
	 * Returns the date of the last share of checkout.
	 *
	 * @return false|string
	 * @throws Alma_Api_Soc_Last_Update_Dates_Exception Date Exception.
	 */
	public function get_last_update_date() {
		$last_update_by_api = $this->alma_settings->get_soc_last_updated_date();

		return gmdate( 'Y-m-d', $last_update_by_api['end_time'] );
	}

	/**
	 * Returns the default date of the last share of checkout.
	 *
	 * @return false|string
	 */
	public function get_default_last_update_date() {
		return gmdate( 'Y-m-d', strtotime( '-2 days' ) );
	}

	/**
	 * Gets the payload to send to API.
	 *
	 * @param string $from_date The start date yyyy-mm-dd formatted.
	 * @param string $end_date The end date yyyy-mm-dd formatted.
	 *
	 * @return array
	 */
	public function get_payload( $from_date, $end_date ) {
		$orders_by_date_range = $this->order_helper->get_orders_by_date_range( $from_date, $end_date );
		$from_date            = $from_date . ' 00:00:00';
		$end_date             = $end_date . ' 23:59:59';

		if ( 0 === count( $orders_by_date_range ) ) {
			return array();
		}

		return array(
			'start_time'      => $from_date,
			'end_time'        => $end_date,
			'orders'          => $this->get_payload_orders( $orders_by_date_range ),
			'payment_methods' => $this->get_payload_payment_methods( $orders_by_date_range ),
		);
	}

	/**
	 * Gets the "orders" payload.
	 *
	 * @param array $orders_by_date_range Array of WC orders.
	 *
	 * @return array
	 */
	protected function get_payload_orders( $orders_by_date_range ) {
		$order_currencies = array();

		foreach ( $orders_by_date_range as $order ) {

			if ( ! isset( $order_currencies[ $order->get_currency() ] ) ) {
				$order_currencies[ $order->get_currency() ] = array(
					'total_order_count' => 0,
					'total_amount'      => 0,
					'currency'          => $order->get_currency(),
				);
			}

			$order_currencies[ $order->get_currency() ]['total_order_count'] += 1;
			$order_currencies[ $order->get_currency() ]['total_amount']      += Alma_Tools_Helper::alma_price_to_cents( $order->get_total() );
		}

		return array_values( $order_currencies );
	}

	/**
	 * Gets the "payment_methods" payload.
	 *
	 * @param array $orders_by_date_range Array of WC orders.
	 *
	 * @return array
	 */
	protected function get_payload_payment_methods( $orders_by_date_range ) {
		$payment_methods_currencies = array();
		foreach ( $orders_by_date_range as $order ) {

			if ( ! isset( $payment_methods_currencies[ $order->get_payment_method() ] ) ) {
				$payment_methods_currencies[ $order->get_payment_method() ] = array();
			}
			if ( ! isset( $payment_methods_currencies[ $order->get_payment_method() ][ $order->get_currency() ] ) ) {
				$payment_methods_currencies[ $order->get_payment_method() ][ $order->get_currency() ] = array(
					'order_count' => 0,
					'amount'      => 0,
				);
			}
			$payment_methods_currencies[ $order->get_payment_method() ][ $order->get_currency() ]['order_count'] += 1;
			$payment_methods_currencies[ $order->get_payment_method() ][ $order->get_currency() ]['amount']      += Alma_Tools_Helper::alma_price_to_cents( $order->get_total() );
		}

		$payment_methods = array();
		foreach ( $payment_methods_currencies as $payment_method_name => $currency_values ) {
			$payment_method                        = array();
			$payment_method['payment_method_name'] = $payment_method_name;
			$orders                                = array();
			foreach ( $currency_values as $currency => $values ) {
				$orders[] = array(
					'order_count' => $values['order_count'],
					'amount'      => $values['amount'],
					'currency'    => $currency,
				);
			}
			$payment_method['orders'] = $orders;
			$payment_methods[]        = $payment_method;
		}

		return $payment_methods;
	}
}
