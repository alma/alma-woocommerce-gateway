<?php
/**
 * Alma_WC_Exception_Api_Fetch_Payments.
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
 * Alma_WC_Exception_Api_Fetch_Payments.
 */
class Alma_WC_Exception_Api_Fetch_Payments extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $payment_id The payment id.
	 */
	public function __construct( $payment_id ) {
		$message = sprintf(
			'Error while fetching payment. Payment id : "%s"',
			$payment_id
		);

		parent::__construct( $message );
	}
}
