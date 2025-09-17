<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\Repository\OrderRepositoryInterface;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;

class OrderRepository implements OrderRepositoryInterface {

	/**
	 * Get an order by its ID, or eventually by its order key or payment ID.
	 *
	 * @param int         $orderId The order ID.
	 * @param string|null $order_key The order key, if available.
	 * @param string|null $payment_id The payment ID, if available.
	 *
	 * @return OrderAdapterInterface The order object.
	 *
	 * @throws ProductRepositoryException
	 */
	public function getById( int $orderId, string $order_key = null, string $payment_id = null ): OrderAdapter {
		$wc_order = wc_get_order( $orderId );

		if ( $wc_order ) {
			return new OrderAdapter( $wc_order );
		}

		if ( $order_key ) {
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );

			if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
				throw new ProductRepositoryException(
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

		throw new ProductRepositoryException( sprintf( 'Undefined Order id: %d (%s / %s)', $orderId, $order_key,
			$payment_id ) );
	}
}
