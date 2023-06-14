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

    const SHOP_ORDER = 'shop_order';

    const WC_PROCESSING = 'wc-processing';

    const WC_COMPLETED = 'wc-completed';

    /**
     * @var array The status order completed.
     */
    protected static $status_order_completed = array(
        self::WC_PROCESSING,
        self::WC_COMPLETED,
    );

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
				'type'         => self::SHOP_ORDER,
				'status'       => self::$status_order_completed,
			)
		);
	}

    /**
     * Gets the WC orders by customer id with limit.
     *
     * @param int $customer_id The customer id.
     * @param int $limit The limit.
     *
     * @return \WC_Order[]
     */
    public function get_orders_by_customer_id( $customer_id, $limit = 10) {
        return wc_get_orders(
            array(
                'customer_id'  => $customer_id,
                'limit'        => $limit,
                'type'         => self::SHOP_ORDER,
                'status'       => self::$status_order_completed,
            )
        );
    }
}
