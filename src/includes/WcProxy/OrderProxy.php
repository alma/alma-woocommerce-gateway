<?php

namespace Alma\Woocommerce\WcProxy;

use Alma\Woocommerce\Exceptions\NoOrderException;
use Alma\Woocommerce\Exceptions\RequirementsException;
use \WC_Order;
use \WC_Order_Refund;

class OrderProxy {

	/**
	 * @param int $order_id
	 * @return WC_Order | WC_Order_Refund
	 * @throws NoOrderException
	 */
	public function get_order_by_id( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			throw new NoOrderException( $order_id );
		}
		return $order;
	}

	/**
	 * @param WC_Order | WC_Order_Refund $order
	 * @return mixed
	 */
	public function get_order_payment_method( $order ) {
		return $order->get_payment_method();
	}

	/**
	 * @param WC_Order | WC_Order_Refund $order
	 * @return string
	 */
	public function get_display_order_reference( $order ) {
		return $order->get_order_number();
	}

	/**
	 * @param WC_Order | WC_Order_Refund $order
	 *
	 * @return string
	 * @throws RequirementsException
	 */
	public function get_alma_payment_id( $order ) {
		if ( empty( $order->get_transaction_id() ) ) {
			throw new RequirementsException( 'No payment id for this order' );
		}
		return $order->get_transaction_id();
	}

}
