<?php

namespace Alma\Gateway\Infrastructure\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Adapter\ProductAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Plugin\Infrastructure\Adapter\ProductAdapterInterface;
use Alma\Plugin\Infrastructure\Repository\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface {

	/**
	 * Get a product by its ID.
	 *
	 * @param int $productId The product ID.
	 *
	 * @return ProductAdapterInterface The Product object.
	 *
	 * @throws ProductRepositoryException
	 */
	public function getById( int $productId ): ProductAdapterInterface {
		$wc_product = wc_get_product( $productId );

		if ( $wc_product ) {
			return new ProductAdapter( $wc_product );
		}

		throw new ProductRepositoryException( sprintf( 'Undefined Product id: %d', $productId ) );
	}
}
