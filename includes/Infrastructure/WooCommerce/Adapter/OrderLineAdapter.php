<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Adapter;

use Alma\API\Domain\OrderLineInterface;
use BadMethodCallException;
use WC_Order_Item;

/**
 * Class OrderLineAdapter
 *
 * This class adapts the WC_Order_Item object to the OrderInterface, allowing dynamic calls to WC_Order_Item methods.
 * It provides methods to retrieve order item details.
 *
 * @method getProduct() see WC_Order_Item::get_product()
 * @method getQuantity() see WC_Order_Item::get_quantity()
 * @method getTotal() see WC_Order_Item::get_total()
 * @method getName() see WC_Order_Item::get_name()
 */
class OrderLineAdapter implements OrderLineInterface {

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
}
