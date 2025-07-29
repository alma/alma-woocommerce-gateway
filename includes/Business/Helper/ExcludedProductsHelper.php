<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

class ExcludedProductsHelper {

	/**
	 * Check if the widget can be displayed on the product page.
	 *
	 * @param $excluded_categories array List of excluded categories.
	 *
	 * @return bool True if the widget can be displayed, false otherwise.
	 */
	public static function can_display_on_product_page( array $excluded_categories = array() ): bool {
		$exclusions = array_intersect(
			$excluded_categories,
			WooCommerceProxy::get_current_product_categories()
		);

		return empty( $exclusions );
	}

	/**
	 * Check if the widget can be displayed on the cart page.
	 *
	 * @param $excluded_categories array List of excluded categories.
	 *
	 * @return bool True if the widget can be displayed, false otherwise.
	 */
	public static function can_display_on_cart_page( array $excluded_categories = array() ): bool {
		$exclusions = array_intersect(
			$excluded_categories,
			WooCommerceProxy::get_cart_items_categories()
		);

		return empty( $exclusions );
	}

	/**
	 * Check if the widget can be displayed on the checkout page.
	 *
	 * @param $excluded_categories array List of excluded categories.
	 *
	 * @return bool True if the widget can be displayed, false otherwise.
	 */
	public static function can_display_on_checkout_page( array $excluded_categories = array() ): bool {
		return self::can_display_on_cart_page( $excluded_categories );
	}
}
