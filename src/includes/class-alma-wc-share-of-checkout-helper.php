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
		$this->start_time = null;
		$this->end_time   = null;
	}

	/**
	 * Set share of checkout "from date". (date BEGIN)
	 *
	 * @param $start_time
	 * @return void
	 */
	public function set_share_of_checkout_from_date( $start_time ) {
		$this->start_time = $start_time . ' 00:00:00';
		$this->set_share_of_checkout_to_date( $start_time );
	}

	/**
	 * Get share of checkout "from date". (date BEGIN)
	 *
	 * @return string|null
	 */
	private function get_share_of_checkout_from_date() {
		if ( isset( $this->start_time ) ) {
			return $this->start_time;
		}

		return date( 'Y-m-d', strtotime( 'yesterday' ) ) . ' 00:00:00';
	}

	/**
	 * Set share of checkout "to date". (date END)
	 *
	 * @param $start_time
	 * @return void
	 */
	public function set_share_of_checkout_to_date( $start_time ) {
		$this->end_time = $start_time . ' 23:59:59';
	}

	/**
	 * Get share of checkout "to date". (date END)
	 *
	 * @return string|null
	 */
	private function get_share_of_checkout_to_date() {
		if ( isset( $this->end_time ) ) {
			return $this->end_time;
		}
		return date( 'Y-m-d', strtotime( 'yesterday' ) ) . ' 23:59:59';
	}

	/**
	 * Returns the date of the last share of checkout.
	 *
	 * @return mixed
	 * @throws Alma_WC_Payment_Validation_Error
	 */
	public function get_last_update_date() {

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			throw new Alma_WC_Payment_Validation_Error( 'api_client_init' );
		}

		try {
			// @todo this can change depending on PHP client version.
			$last_update_by_api = $alma->shareOfCheckout->getLastUpdateDates();
			error_log( '$last_update_by_api = ' );
			error_log( gettype( $last_update_by_api ) );
			error_log( serialize( $last_update_by_api ) );

			return $last_update_by_api['end_time'];
		} catch ( \Exception $e ) {
			error_log( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
			$this->logger->error( 'Error getting getLastUpdateDates for ShareOfCheckout : ' . $e->getMessage() );
		}

		return date( 'Y-m-d', strtotime( '-2 days' ) );
	}

	/**
	 * Gets the WC orders in a date range.
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
	 * Gets the payload to send to API.
	 *
	 * @return array
	 */
	public function get_payload() {
		$from                 = $this->get_share_of_checkout_from_date();
		$to                   = $this->get_share_of_checkout_to_date();
		$orders_by_date_range = $this->get_orders_by_date_range( $from, $to );
		return array(
			'start_time'      => $from,
			'end_time'        => $to,
			'orders'          => $this->get_payload_orders( $orders_by_date_range ),
			'payment_methods' => $this->get_payload_payment_methods( $orders_by_date_range ),
		);
	}

	/**
	 * Send data for one day to API.
	 *
	 * @return array|void
	 */
	public function share_day() {
		error_log( '---------------------------' );
		error_log( 'function share_day()' );

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		$res = array();
		try {
			$res = $alma->shareOfCheckout->share( $this->get_payload() );
		} catch ( RequestError $e ) {
			$this->logger->info( 'Alma_WC_Share_Of_Checkout_Helper::share error get message :', array( $e->getMessage() ) );
		}
		return $res;
	}
}



