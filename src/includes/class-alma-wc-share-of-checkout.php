<?php
/**
 * Alma share of checkout
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Share_Of_Checkout
 */
class Alma_WC_Share_Of_Checkout {

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
		$this->logger = new Alma_WC_Logger();
	}

	/**
	 * Init function.
	 */
	public function init() {
		return;
		add_action( 'init', array( $this, 'bootstrap' ) );
	}

	/**
	 * Bootstrap function.
	 */
	public function bootstrap() {
		$result = $this->get_share_of_checkout_payload( '2022-01-01', '2022-03-30' );
		echo '<pre>';
		print_r( $result );
		echo '</pre>';
		exit;
	}

	/**
	 * Gets the orders in a date range.
	 *
	 * @param string $from The date from.
	 * @param string $to The date to.
	 * @return array
	 */
	private function get_orders_by_date_range( $from, $to ) {
		$args = array(
			'date_created' => $from . '...' . $to,
		);
		return wc_get_orders( $args );
	}

	/**
	 * Gets orders for a date range.
	 *
	 * @param string $from The date from.
	 * @param string $to The date to.
	 * @return array
	 */
	public function get_share_of_checkout_payload( $from, $to ) {
		$orders_by_date_range = $this->get_orders_by_date_range( $from, $to );
		return array(
			'start_time'      => $from,
			'end_time'        => $to,
			'orders'          => $this->get_payload_orders( $orders_by_date_range ),
			'payment_methods' => $this->get_payload_payment_methods( $orders_by_date_range ),
		);
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
				);
			}
			$order_currencies[ $order->get_currency() ]['total_order_count'] += 1;
			$order_currencies[ $order->get_currency() ]['total_amount']      += alma_wc_price_to_cents( $order->get_total() );
		}
		$orders = array();
		foreach ( $order_currencies as $currency => $value ) {
			$orders[] = array(
				'total_order_count' => $value['total_order_count'],
				'total_amount'      => $value['total_amount'],
				'currency'          => $currency,
			);
		}
		return $orders;
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
		foreach ( $payment_methods_currencies as $payment_method => $currency_values ) {
			$payment_methods['payment_method_name'] = $payment_method;
			foreach ( $currency_values as $currency => $values ) {
				$payment_methods['orders'] = array(
					'order_count' => $values['order_count'],
					'amount'      => $values['amount'],
					'currency'    => $currency,
				);
			}
		}
		return $payment_methods;
	}

	/**
	 * Does the call to alma API to share the checkout datas.
	 *
	 * @return mixed
	 */
	public function share_of_checkout() {
		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		try {
			$share = $alma->payments->shareOfCheckout( $this->get_share_of_checkout_payload( $from, $to ) );
		} catch ( RequestError $e ) {
			$this->logger->log_stack_trace( 'Error while sharing checkout: ', $e );
			return;
		}
	}

}










