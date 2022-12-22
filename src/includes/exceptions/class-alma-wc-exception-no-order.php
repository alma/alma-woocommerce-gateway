<?php
/**
 * Alma_WC_Exception_No_Order.
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
 * Alma_WC_Exception_No_Order
 */
class Alma_WC_Exception_No_Order extends Exception {

	/**
	 * Constructor.
	 *
	 * @param string $order_id  The order id.
	 * @param string $order_key The order key.
	 */
	public function __construct( $order_id, $order_key ) {

		parent::__construct( sprintf( "Can't find order '%s' (key: %s). Order Keys do not match.", $order_id, $order_key ) );
	}
}
