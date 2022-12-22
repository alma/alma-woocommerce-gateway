<?php
/**
 * Alma_WC_Exception_Api_Partial_Refund.
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
 * Alma_WC_Exception_Api_Partial_Refund.
 */
class Alma_WC_Exception_Api_Partial_Refund extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $merchant_reference The merchant reference.
	 * @param string $amount The amount to refund.
	 */
	public function __construct( $transaction_id, $merchant_reference, $amount ) {
		$message = sprintf(
			'Error while full refund. Transaction id : "%s", Merchant reference : "%s", Amount : "%s"',
			$transaction_id,
			$merchant_reference,
			$amount
		);

		parent::__construct( $message );
	}
}
