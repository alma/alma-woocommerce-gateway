<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Adapter\ProductAdapterInterface;
use Alma\API\Domain\Exception\CoreException;
use Alma\API\Domain\Repository\ProductRepositoryInterface;
use Alma\Gateway\Infrastructure\Adapter\ProductAdapter;

class ProductRepository implements ProductRepositoryInterface {

	/**
	 * Find a product by its ID.
	 *
	 * @param int $productId The product ID.
	 *
	 * @return ProductAdapterInterface The Product object.
	 *
	 * @throws CoreException
	 */
	public function findById( int $productId ): ProductAdapterInterface {
		$wc_product = wc_get_product( $productId );

		if ( $wc_product ) {
			return new ProductAdapter( $wc_product );
		}

		throw new CoreException( sprintf( 'Undefined Product id: %d', $productId ) );
	}
}
