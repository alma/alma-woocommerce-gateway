<?php
/**
 * Alma share of checkout helper
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Share_Of_Checkout_Helper
 */
class Alma_WC_Share_Of_Checkout_Helper {

	/**
	 * Order helper
	 *
	 * @var Alma_WC_Helper_Order
	 */
	private $order_helper;

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->order_helper = new Alma_WC_Helper_Order();
		$this->logger       = new Alma_WC_Logger();
	}

	/**
	 * Get share of checkout "from date". (date BEGIN)
	 *
	 * @param string $start_date The start date yyyy-mm-dd formatted.
	 * @return string
	 */
	private function get_from_date( $start_date ) {
		return $start_date . ' 00:00:00';
	}

	/**
	 * Get share of checkout "to date". (date END)
	 *
	 * @param string $start_date The start date yyyy-mm-dd formatted.
	 * @return string
	 */
	private function get_to_date( $start_date ) {
		return $start_date . ' 23:59:59';
	}

	/**
	 * Returns the date of the last share of checkout.
	 *
	 * @return int (timestamp)
	 */
	public function get_last_update_date() {
		$last_update_date = gmdate( 'Y-m-d', strtotime( '-2 days' ) );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return $last_update_date;
		}

		try {
			$last_update_by_api = $alma->shareOfCheckout->getLastUpdateDates(); // phpcs:ignore
			$last_update_date   = gmdate( 'Y-m-d', $last_update_by_api['end_time'] );
		} catch ( RequestError $e ) {
			$this->logger->error( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
		}
		return $last_update_date;
	}

	/**
	 * Gets the "orders" payload.
	 *
	 * @param array $orders_by_date_range Array of WC orders.
	 * @return array
	 */
	private function get_payload_orders( $orders_by_date_range ) {
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
			$order_currencies[ $order->get_currency() ]['total_amount']      += alma_wc_price_to_cents( $order->get_total() );
		}
		return array_values( $order_currencies );
	}

	/**
	 * Gets the "payment_methods" payload.
	 *
	 * @param array $orders_by_date_range Array of WC orders.
	 * @return array
	 */
	private function get_payload_payment_methods( $orders_by_date_range ) {
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
			$payment_methods_currencies[ $order->get_payment_method() ][ $order->get_currency() ]['amount']      += alma_wc_price_to_cents( $order->get_total() );
		}

		$payment_methods = array();
		foreach ( $payment_methods_currencies as $payment_method_name => $currency_values ) {
			$payment_method                        = array();
			$payment_method['payment_method_name'] = $payment_method_name;
			$orders                                = array();
			foreach ( $currency_values as $currency => $values ) {
				$orders   = array();
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

	/**
	 * Gets the payload to send to API.
	 *
	 * @param string $start_date The start date yyyy-mm-dd formatted.
	 *
	 * @return array
	 */
	public function get_payload( $start_date ) {
		$from                 = $this->get_from_date( $start_date );
		$to                   = $this->get_to_date( $start_date );
		$orders_by_date_range = $this->order_helper->get_orders_by_date_range( $from, $to );
		return array(
			'start_time'      => $from,
			'end_time'        => $to,
			'orders'          => $this->get_payload_orders( $orders_by_date_range ),
			'payment_methods' => $this->get_payload_payment_methods( $orders_by_date_range ),
		);
	}
}
