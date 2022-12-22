<?php
/**
 * Alma_WC_Exception_Api_Create_Payments.
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
 * Alma_WC_Exception_Api_Create_Payments
 */
class Alma_WC_Exception_Api_Create_Payments extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $order_id The order id.
	 * @param array  $fee_plan_definition   The fee plans.
	 */
	public function __construct( $order_id, $fee_plan_definition ) {
		$message = sprintf(
			'Error while creating payment. Order id : %s, Plan definition : %s',
			$order_id,
			wp_json_encode( $fee_plan_definition )
		);

		parent::__construct( $message );
	}
}
