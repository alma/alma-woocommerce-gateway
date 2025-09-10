<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\Gateway\Application\Helper\DisplayHelper;

class CartAdapter implements CartAdapterInterface {

	/**
	 * Get the cart total in cents.
	 *
	 * @return int
	 */
	public function getCartTotal(): int {

		return DisplayHelper::price_to_cent( WC()->cart->get_total( null ) );
	}

	/**
	 * Empty the cart.
	 *
	 * @return void
	 */
	public function emptyCart(): void {
		wc()->cart->empty_cart();
	}

	/**
	 * Get the cart items categories.
	 *
	 * @return array An array of cart items categories.
	 */
	public function getCartItemsCategories(): array {

		$category_ids = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];

			if ( ! $product || ! $product->get_id() ) {
				continue;
			}

			$terms = get_the_terms( $product->get_id(), 'product_cat' );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$category_ids = array_merge( $category_ids, wp_list_pluck( $terms, 'term_id' ) );
			}
		}

		return array_values( array_unique( $category_ids ) );
	}
}
