<?php

namespace Alma\Gateway\Business\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Is it the best name for this class? Maybe not, but it is short and simple.
 * It is used to handle display-related tasks such as formatting strings,
 */
class DisplayHelper {

	const ALMA_L10N_DOMAIN = 'alma-gateway-for-woocommerce';

	/**
	 * Format an amount to a string with the Euro symbol.
	 *
	 * @param float $amount The amount to format.
	 *
	 * @return string
	 */
	public static function amount( float $amount ): string {
		if ( fmod( $amount, 1 ) === 0.0 ) {
			return sprintf( '%.0f €', $amount );
		} else {
			return sprintf( '%.2f €', $amount );
		}
	}

	/**
	 * Convert a price in euros to cents.
	 *
	 * @param float $price The price in euros.
	 *
	 * @return int The price in cents.
	 */
	public static function price_to_cent( float $price ): int {

		return $price * 100;
	}

	/**
	 * Convert a price in cents to euros.
	 *
	 * @param int $price The price in cents.
	 *
	 * @return float The price in euros.
	 */
	public static function price_to_euro( int $price ): float {
		return $price / 100;
	}
}
