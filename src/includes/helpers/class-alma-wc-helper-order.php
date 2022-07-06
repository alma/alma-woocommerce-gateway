<?php
/**
 * Alma order helper.
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Helper_Order
 */
class Alma_WC_Helper_Order {

	/**
	 * Gets the WC orders in a date range.
	 *
	 * @param string $from The date from.
	 * @param string $to The date to.
	 * @return WC_Order[]
	 */
	public function get_orders_by_date_range( $from, $to ) {
		$args = array(
			'date_created' => $from . '...' . $to,
			'type'         => 'shop_order',
		);
		return wc_get_orders( $args );
	}

}
