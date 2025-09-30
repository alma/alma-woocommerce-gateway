<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\OrderLineAdapterInterface;
use Alma\API\Domain\Adapter\ProductAdapterInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;
use BadMethodCallException;
use WC_Order_Item;

/**
 * Class OrderLineAdapter
 *
 * This class adapts the WC_Order_Item object to the OrderAdapterInterface, allowing dynamic calls to WC_Order_Item methods.
 * It provides methods to retrieve order item details.
 *
 */
class OrderLineAdapter implements OrderLineAdapterInterface {

	private WC_Order_Item $order_item;

	public function __construct( WC_Order_Item $order_item ) {
		$this->order_item = $order_item;
	}

	/**
	 * Dynamic call to all WC_Order_Item methods
	 */
	public function __call( $name, $arguments ) {
		// Convert camelCase to snake_case
		$snake_case_name = strtolower( preg_replace( '/(?<!^)([A-Z0-9])/', '_$1', $name ) );

		if ( method_exists( $this->order_item, $snake_case_name ) ) {
			return $this->order_item->{$snake_case_name}( ...$arguments );
		}

		throw new BadMethodCallException( "Méthod $name (→ $snake_case_name) does not exists on WC_Order_Item" );
	}

	public function getTotal(): int {
		return DisplayHelper::price_to_cent( $this->order_item->get_total() );
	}

	public function getProduct(): ProductAdapterInterface {
		return new ProductAdapter( $this->order_item->get_product() );
	}

	public function getQuantity(): int {
		return $this->order_item->get_quantity();
	}

	public function getName(): string {
		return $this->order_item->get_name();
	}
}
