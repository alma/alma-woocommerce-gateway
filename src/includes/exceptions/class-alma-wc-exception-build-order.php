<?php
/**
 * Alma_WC_Exception_Build_Order.
 *
 * @since 4.0.0
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes/exceptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_WC_Exception_Build_Order.
 */
class Alma_WC_Exception_Build_Order extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $order_id  The order id.
	 * @param string $order_key The order key.
	 * @param string $payment_id    The payment id.
	 */
	public function __construct( $order_id, $order_key, $payment_id ) {
		$message = sprintf(
			'Error building order associated to payment. Order id "%s", Order Key "%s", Payment Id "%s"',
			$order_id,
			$order_key,
			$payment_id
		);

		parent::__construct( $message );
	}
}
