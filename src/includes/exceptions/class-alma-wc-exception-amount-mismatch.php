<?php
/**
 * Alma_WC_Exception_Amount_Mismatch.
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
 * Alma_WC_Exception_Amount_Mismatch.
 */
class Alma_WC_Exception_Amount_Mismatch extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $payment_id The payment id.
	 * @param string $order_id  The order id.
	 * @param string $order_total   The order total.
	 * @param string $purchase_amount   The purchase amount.
	 */
	public function __construct( $payment_id, $order_id, $order_total, $purchase_amount ) {
		$message = sprintf(
			'FlagAsPotentialFraud request error (total amount issue), Payment Id "%s". Order "%s" total "(%s)" does not match purchase amount of  "(%s)"',
			$payment_id,
			$order_id,
			$order_total,
			$purchase_amount
		);

		parent::__construct( $message );
	}
}
