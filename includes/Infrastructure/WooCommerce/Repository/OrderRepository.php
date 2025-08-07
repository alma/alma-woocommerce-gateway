<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Repository;

use Alma\API\Domain\OrderInterface;
use Alma\API\Domain\OrderRepositoryInterface;
use Alma\Gateway\Infrastructure\WooCommerce\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\WooCommerce\Exception\CoreException;

class OrderRepository implements OrderRepositoryInterface {

	/**
	 * Find an order by its ID, or eventually by its order key or payment ID.
	 *
	 * @param int         $orderId The order ID.
	 * @param string|null $order_key The order key, if available.
	 * @param string|null $payment_id The payment ID, if available.
	 *
	 * @return OrderInterface The order object.
	 *
	 * @throws CoreException
	 */
	public function findById( int $orderId, string $order_key = null, string $payment_id = null ): OrderInterface {
		$wc_order = wc_get_order( $orderId );

		if ( $wc_order ) {
			return new OrderAdapter( $wc_order );
		}

		if ( $order_key ) {
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );

			if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
				throw new CoreException(
					sprintf(
						'Undefined Order id: %d (%s / %s)',
						$orderId,
						$order_key,
						$payment_id
					)
				);
			}

			return new OrderAdapter( $order );
		}

		throw new CoreException( sprintf( 'Undefined Order id: %d (%s / %s)', $orderId, $order_key, $payment_id ) );
	}
}
