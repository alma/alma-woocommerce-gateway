<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\ProductCategoryRepositoryInterface;

class ProductCategoryRepository implements ProductCategoryRepositoryInterface {

	/**
	 * Get the product categories.
	 *
	 * @return array The product categories, as an associative array with term IDs as keys and names as values.
	 */
	public function getAll(): array {
		$product_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'orderby'    => 'name',
				'order'      => 'asc',
				'hide_empty' => false,
			)
		);

		return array_combine(
			array_column( $product_categories, 'term_id' ),
			array_column( $product_categories, 'name' )
		);
	}

	/**
	 * Get the product categories for a specific product.
	 *
	 * @param int $productId The product ID.
	 *
	 * @return array The product categories as an array of category names.
	 */
	public function findByProductId( int $productId ): array {
		return explode( ',', wp_strip_all_tags( wc_get_product_category_list( $productId, ',' ) ) );
	}
}
