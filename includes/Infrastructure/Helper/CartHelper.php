<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class CartHelper {
	/**
	 * Generate a unique cart ID suitable for BIGINT(20) unsigned storage.
	 *
	 * @return int
	 */
	public static function generateUniqueCartId(): int {
		// Get current timestamp (milliseconds)
		$timestamp = round( microtime( true ) * 1000 );

		// Add random component (5 digits)
		$random = mt_rand( 10000, 99999 );

		// Combine timestamp + random to ensure uniqueness
		// Format: TTTTTTTTTTTTTRRRR
		$id = $timestamp . $random;

		// Ensure it fits in BIGINT(20) unsigned max value
		$max_bigint = '18446744073709551615';
		if ( strlen( $id ) > strlen( $max_bigint ) ) {
			$id = substr( $id, 0, strlen( $max_bigint ) );
		}

		return $id;
	}

	/**
	 * Get the number of decimals used for cart prices.
	 *
	 * @return int The number of decimals for cart prices.
	 */
	public static function getCartPriceDecimalsNumber(): int {
		return wc_get_price_decimals();
	}
}
