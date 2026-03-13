<?php

namespace Alma\Gateway\Infrastructure\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Plugin\Infrastructure\Adapter\ProductAdapterInterface;
use BadMethodCallException;
use WC_Product;

/**
 * Class ProductAdapter
 *
 * This class adapts the WC_Order object to the OrderAdapterInterface, allowing dynamic calls to WC_Order methods.
 * It provides methods to retrieve order details, update order status, and manage order notes.
 */
class ProductAdapter implements ProductAdapterInterface {

	private WC_Product $wc_product;

	public function __construct( WC_Product $product ) {
		$this->wc_product = $product;
	}

	/**
	 * Dynamic call to all WC_Order methods
	 *
	 * @throws BadMethodCallException if the method does not exist on WC_Product
	 */
	public function __call( string $name, array $arguments ) {
		// Convert camelCase to snake_case
		$snake_case_name = strtolower( preg_replace( '/(?<!^)([A-Z0-9])/', '_$1', $name ) );

		if ( method_exists( $this->wc_product, $snake_case_name ) ) {
			return $this->wc_product->{$snake_case_name}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $snake_case_name) does not exists on WC_Product" );
	}

	public function getPrice(): int {
		return DisplayHelper::price_to_cent( (float) $this->wc_product->get_price() );
	}

	public function getCategoryIds(): array {
		return $this->wc_product->get_category_ids();
	}

	public function getCategorySlugs(): array {
		$category_ids = $this->wc_product->get_category_ids();
		$slugs        = [];
		foreach ( $category_ids as $category_id ) {
			$term = get_term( $category_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}

		return $slugs;
	}

	public function getId(): int {
		return $this->wc_product->get_id();
	}

	public function getSku(): string {
		return $this->wc_product->get_sku();
	}

	public function getPermalink(): string {
		return $this->wc_product->get_permalink();
	}

	public function getImageId(): int {
		return (int) $this->wc_product->get_image_id();
	}

	public function needsShipping(): bool {
		return $this->wc_product->needs_shipping();
	}
}
