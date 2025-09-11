<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\ProductAdapterInterface;
use BadMethodCallException;
use WC_Product;

/**
 * Class ProductAdapter
 *
 * This class adapts the WC_Order object to the OrderAdapterInterface, allowing dynamic calls to WC_Order methods.
 * It provides methods to retrieve order details, update order status, and manage order notes.
 *
 * @method getId()
 */
class ProductAdapter implements ProductAdapterInterface {

	private WC_Product $wc_product;

	public function __construct( WC_Product $product ) {
		$this->wc_product = $product;
	}

	/**
	 * Dynamic call to all WC_Order methods
	 */
	public function __call( string $name, array $arguments ) {
		// Convert camelCase to snake_case
		$snake_case_name = strtolower( preg_replace( '/(?<!^)([A-Z0-9])/', '_$1', $name ) );

		if ( method_exists( $this->wc_product, $snake_case_name ) ) {
			return $this->wc_product->{$snake_case_name}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $snake_case_name) does not exists on WC_Product" );
	}

	public function getPrice(): float {
		return (float) $this->wc_product->get_price();
	}

	public function getCategoryIds(): array {
		return $this->wc_product->get_category_ids();
	}
}
