<?php
/**
 * Order proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */

namespace Alma\Woocommerce\WcProxy;

use Alma\Woocommerce\Exceptions\NoOrderException;
use Alma\Woocommerce\Exceptions\RequirementsException;
use WC_Order;
use WC_Order_Refund;

/**
 * Order proxy.
 */
class OrderProxy {

	/**
	 * Get order by id.
	 *
	 * @param int $order_id Order id.
	 * @return WC_Order | WC_Order_Refund Order.
	 *
	 * @throws NoOrderException No order exception.
	 */
	public function get_order_by_id( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			throw new NoOrderException( $order_id );
		}
		return $order;
	}

	/**
	 * Get order by reference.
	 *
	 * @param WC_Order | WC_Order_Refund $order Order.
	 *
	 * @return mixed
	 */
	public function get_order_payment_method( $order ) {
		return $order->get_payment_method();
	}

	/**
	 * Get order by reference.
	 *
	 * @param WC_Order | WC_Order_Refund $order Order.
	 *
	 * @return string
	 */
	public function get_display_order_reference( $order ) {
		return $order->get_order_number();
	}

	/**
	 * Get order by reference.
	 *
	 * @param WC_Order | WC_Order_Refund $order Order.
	 *
	 * @return string
	 *
	 * @throws RequirementsException Requirements exception.
	 */
	public function get_alma_payment_id( $order ) {
		if ( empty( $order->get_transaction_id() ) ) {
			throw new RequirementsException( 'No payment id for this order' );
		}
		return $order->get_transaction_id();
	}
}
