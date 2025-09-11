<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\ProductCategoryRepositoryInterface;

class ProductCategoryRepository implements ProductCategoryRepositoryInterface {

	/**
	 * Get the product categories.
	 *
	 * @return array The product categories, as an associative array with term IDs as keys and names as values.
	 */
	public static function getProductCategories(): array {
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
}
