<?php
/**
 * Alma_WC_Exception_Api_Trigger_Payments.
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
 * Alma_WC_Exception_Api_Trigger_Payments.
 */
class Alma_WC_Exception_Api_Trigger_Payments extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $transaction_id The transaction id.
	 */
	public function __construct( $transaction_id ) {
		$message = sprintf(
			'Error while triggering payment. Transaction id : "%s"',
			$transaction_id
		);

		parent::__construct( $message );
	}
}
