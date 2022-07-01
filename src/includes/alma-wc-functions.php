<?php
/**
 * HELPER FUNCTIONS
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Converts a float price to its integer cents value, used by the API.
 *
 * @param float $price Price.
 *
 * @return integer
 */
function alma_wc_price_to_cents( $price ) {
	return (int) ( round( $price * 100 ) );
}

/**
 * Converts an integer price in cents to a float price in the used currency units.
 *
 * @param int $price Price.
 *
 * @return float
 */
function alma_wc_price_from_cents( $price ) {
	return (float) ( $price / 100 );
}

/**
 * Format bps using default WooCommerce price renderer.
 *
 * @param int $bps Bps in cents.
 *
 * @return string
 *
 * @see wc_price()
 */
function alma_wc_format_percent_from_bps( $bps ) {
	$decimal_separator  = wc_get_price_decimal_separator();
	$thousand_separator = wc_get_price_thousand_separator();
	$decimals           = wc_get_price_decimals();
	$price_format       = get_woocommerce_price_format();
	$negative           = $bps < 0;
	$bps                = number_format( alma_wc_price_from_cents( $bps ), $decimals, $decimal_separator, $thousand_separator );
	$formatted_bps      = ( $negative ? '-' : '' ) . sprintf( $price_format, '<span class="woocommerce-Price-currencySymbol">&#37;</span>', $bps );

	return '<span class="woocommerce-Price-amount amount">' . $formatted_bps . '</span>';
}

/**
 * Format price using default WooCommerce price renderer.
 *
 * @param int   $price Price in cents.
 * @param array $args (default: array()).
 *
 * @return string
 *
 * @see wc_price()
 */
function alma_wc_format_price_from_cents( $price, $args = array() ) {
	return wc_price( alma_wc_price_from_cents( $price ), array_merge( array( 'currency' => 'EUR' ), $args ) );
}

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * Taken from WooCommerce, to which it was added in version 3.0.0, and we need support for older WC versions.
 *
 * @param string $string String to convert.
 * @return bool
 */
function alma_wc_string_to_bool( $string ) {
	return is_bool( $string )
		? $string
		: ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
}

/**
 * Sort Plans Keys by type first (pnx before pay later), then installments count
 *
 * @param string $plan_key_1 The first plan key to compare to.
 * @param string $plan_key_2 The second plan key to compare to.
 */
function alma_wc_usort_plans_keys( $plan_key_1, $plan_key_2 ) {
	if ( $plan_key_1 === $plan_key_2 ) {
		return 0;
	}
	$match_1 = alma_wc_match_plan_key_pattern( $plan_key_1 );
	$match_2 = alma_wc_match_plan_key_pattern( $plan_key_2 );
	if ( ! $match_1 || ! $match_2 ) {
		return 0;
	}

	if ( $match_1['deferred_days'] > 0 ) {
		if ( $match_2['deferred_months'] > 0 ) {
			return -1;
		}
		if ( $match_2['deferred_days'] > 0 ) {
			if ( $match_1['deferred_days'] < $match_2['deferred_days'] ) {
				return -1;
			}
		}
		return 1;
	}
	if ( $match_1['deferred_months'] > 0 ) {
		if ( $match_2['deferred_months'] > 0 ) {
			if ( $match_1['deferred_months'] < $match_2['deferred_months'] ) {
				return -1;
			} else {
				return 1;
			}
		}
		return 1;
	}
	if ( $match_1['installments'] < $match_2['installments'] ) {
		return -1;
	}

	return 1;
}

/**
 * Check if a plan key match our pattern (then return array with chunks of the pattern)
 * chunks are :
 * - key
 * - kind
 * - installments
 * - deferred_days
 * - deferred_months
 *
 * @param string $plan_key The plan key to test.
 *
 * @return false|array
 */
function alma_wc_match_plan_key_pattern( $plan_key ) {
	$matches = array();
	if ( preg_match( '/^(general|pos)_([0-9]+)_([0-9]+)_([0-9]+)$/', $plan_key, $matches ) ) {

		return array_combine(
			array(
				'key',
				'kind',
				'installments',
				'deferred_days',
				'deferred_months',
			),
			$matches
		);
	}

	return false;
}
