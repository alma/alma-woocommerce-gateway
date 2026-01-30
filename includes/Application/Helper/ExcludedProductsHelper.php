<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Plugin\Application\Helper\ExcludedProductsHelperInterface;
use Alma\Plugin\Infrastructure\Adapter\CartAdapterInterface;
use Alma\Plugin\Infrastructure\Adapter\ProductAdapterInterface;

class ExcludedProductsHelper implements ExcludedProductsHelperInterface {

	/**
	 * Check if the widget can be displayed on the product page.
	 *
	 * @param ProductAdapterInterface $product The product to check.
	 * @param array                   $excludedCategories List of excluded categories.
	 *
	 * @return bool True if the widget can be displayed, false otherwise.
	 */
	public function canDisplayOnProductPage( ProductAdapterInterface $product, array $excludedCategories = array() ): bool {
		$exclusions = array_intersect(
			$excludedCategories,
			$product->getCategoryIds()
		);

		return empty( $exclusions );
	}

	/**
	 * Check if the widget can be displayed on the cart page.
	 *
	 * @param CartAdapterInterface $cartAdapter The cart adapter.
	 * @param array                $excludedCategories List of excluded categories.
	 *
	 * @return bool True if the widget can be displayed, false otherwise.
	 */
	public function canDisplayOnCartPage( CartAdapterInterface $cartAdapter, array $excludedCategories = array() ): bool {
		$exclusions = array_intersect(
			$excludedCategories,
			$cartAdapter->getCartItemsCategories()
		);

		return empty( $exclusions );
	}

	/**
	 * Check if the widget can be displayed on the checkout page.
	 *
	 * @param CartAdapterInterface $cartAdapter The cart adapter.
	 * @param array                $excludedCategories List of excluded categories.
	 *
	 * @return bool True if the widget can be displayed, false otherwise.
	 */
	public function canDisplayOnCheckoutPage( CartAdapterInterface $cartAdapter, array $excludedCategories = array() ): bool {
		return $this->canDisplayOnCartPage( $cartAdapter, $excludedCategories );
	}
}
