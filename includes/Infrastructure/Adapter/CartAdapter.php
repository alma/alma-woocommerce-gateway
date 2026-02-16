<?php

namespace Alma\Gateway\Infrastructure\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Plugin\Infrastructure\Adapter\CartAdapterInterface;
use WC_Cart;

class CartAdapter implements CartAdapterInterface {

	private ?WC_Cart $cart = null;

	public function __construct( ?WC_Cart $cart ) {
		$this->cart = $cart;
	}

	/**
	 * Get the cart total in cents.
	 *
	 * @return int
	 */
	public function getCartTotal(): int {
		if ( ! $this->cart ) {
			return 0;
		}

		return DisplayHelper::price_to_cent( $this->cart->get_total( null ) );
	}

	/**
	 * Empty the cart.
	 *
	 * @return void
	 */
	public function emptyCart(): void {
		if ( ! $this->cart ) {
			return;
		}
		$this->cart->empty_cart();
	}

	/**
	 * Get the cart items categories slugs.
	 *
	 * @return array An array of cart items categories.
	 */
	public function getCartItemsCategoriesSlugs(): array {

		if ( ! $this->cart ) {
			return [];
		}

		$category_slugs = array();

		foreach ( $this->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];

			if ( ! $product || ! $product->get_id() ) {
				continue;
			}

			$terms = get_the_terms( $product->get_id(), 'product_cat' );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$category_slugs = array_merge( $category_slugs, wp_list_pluck( $terms, 'slug' ) );
			}
		}

		return array_values( array_unique( $category_slugs ) );
	}
}
