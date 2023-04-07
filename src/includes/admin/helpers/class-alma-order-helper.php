<?php
/**
 * Alma_Order_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/admin/helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Order_Helper.
 */
class Alma_Order_Helper {

	/**
	 * Gets the WC orders in a date range.
	 *
	 * @param string $from The date from.
	 * @param string $to The date to.
	 *
	 * @return \WC_Order[]
	 */
	public function get_orders_by_date_range( $from, $to ) {
		return wc_get_orders(
			array(
				'date_created' => $from . '...' . $to,
				'type'         => 'shop_order',
			)
		);
	}
}
