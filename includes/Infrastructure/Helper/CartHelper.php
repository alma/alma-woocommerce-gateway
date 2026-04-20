<?php

namespace Alma\Gateway\Infrastructure\Helper;


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class CartHelper {
	/**
	 * Number of bits reserved for the random component.
	 */
	private const RANDOM_BITS = 20;

	/**
	 * Generate a unique cart ID suitable for BIGINT(20) unsigned storage.
	 *
	 * Uses bit-shifting to combine a Unix timestamp (seconds) in the upper
	 * bits with a cryptographically random value in the lower 20 bits.
	 * This produces a 64-bit positive integer (~16 digits) that is:
	 * - naturally sortable by creation time,
	 * - unique enough for concurrent carts (1 048 576 possible values per second),
	 * - safe for PHP 64-bit int (max 9.2 × 10^18) and MySQL BIGINT(20) unsigned.
	 *
	 * @return int
	 */
	public static function generateUniqueCartId(): int {
		$timestamp = time();
		$random    = random_int( 0, ( 1 << self::RANDOM_BITS ) - 1 );

		return ( $timestamp << self::RANDOM_BITS ) | $random;
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
