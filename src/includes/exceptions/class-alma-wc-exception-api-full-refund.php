<?php
/**
 * Alma_WC_Exception_Api_Full_Refund.
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
 * Alma_WC_Exception_Api_Full_Refund.
 */
class Alma_WC_Exception_Api_Full_Refund extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $merchant_reference The merchant reference.
	 */
	public function __construct( $transaction_id, $merchant_reference ) {
		$message = sprintf(
			'Error while full refund. Transaction id : %s, Merchant reference : %s',
			$transaction_id,
			$merchant_reference
		);

		parent::__construct( $message );
	}
}
