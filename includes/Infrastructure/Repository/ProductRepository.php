<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Adapter\ProductAdapterInterface;
use Alma\API\Domain\Repository\ProductRepositoryInterface;
use Alma\Gateway\Infrastructure\Adapter\ProductAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;

class ProductRepository implements ProductRepositoryInterface {

	/**
	 * Find a product by its ID.
	 *
	 * @param int $productId The product ID.
	 *
	 * @return ProductAdapterInterface The Product object.
	 *
	 * @throws ProductRepositoryException
	 */
	public function findById( int $productId ): ProductAdapterInterface {
		$wc_product = wc_get_product( $productId );

		if ( $wc_product ) {
			return new ProductAdapter( $wc_product );
		}

		throw new ProductRepositoryException( sprintf( 'Undefined Product id: %d', $productId ) );
	}
}
