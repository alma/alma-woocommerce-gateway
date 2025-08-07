<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Repository;

use Alma\API\Domain\ProductInterface;
use Alma\API\Domain\ProductRepositoryInterface;
use Alma\Gateway\Infrastructure\WooCommerce\Adapter\ProductAdapter;
use Alma\Gateway\Infrastructure\WooCommerce\Exception\CoreException;

class ProductRepository implements ProductRepositoryInterface {

	/**
	 * Find a product by its ID.
	 *
	 * @param int $productId The product ID.
	 *
	 * @return ProductInterface The Product object.
	 *
	 * @throws CoreException
	 */
	public function findById( int $productId ): ProductInterface {
		$wc_product = wc_get_product( $productId );

		if ( $wc_product ) {
			return new ProductAdapter( $wc_product );
		}

		throw new CoreException( sprintf( 'Undefined Product id: %d', $productId ) );
	}
}
