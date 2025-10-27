<?php

namespace Alma\Gateway\Infrastructure\Helper;

use WC_Blocks_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlockHelper
 */
class BlockHelper {

	/**
	 * Conditional function that check if Cart page use Cart Blocks
	 *
	 * @param int $pageId The ID of the page to check. Default is 0, which means the default cart page.
	 *
	 * @return bool True if the cart page uses Cart Blocks, false otherwise.
	 */
	public static function has_woocommerce_cart_blocks( int $pageId = 0 ): bool {
		if ( 0 === $pageId ) {
			$pageId = wc_get_page_id( 'cart' );
		}

		return WC_Blocks_Utils::has_block_in_page( $pageId, 'woocommerce/cart' );
	}

	/**
	 * Conditional function that check if Checkout page use Checkout Blocks
	 *
	 * @param int $pageId The ID of the page to check. Default is 0, which means the default checkout page.
	 *
	 * @return bool True if the checkout page uses Checkout Blocks, false otherwise.
	 */
	public static function has_woocommerce_checkout_blocks( int $pageId = 0 ): bool {
		if ( 0 === $pageId ) {
			$pageId = wc_get_page_id( 'checkout' );
		}

		return WC_Blocks_Utils::has_block_in_page( $pageId, 'woocommerce/checkout' );
	}
}
