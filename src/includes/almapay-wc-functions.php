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
function almapay_wc_price_to_cents( $price ) {
	return (int) ( round( $price * 100 ) );
}

/**
 * Converts an integer price in cents to a float price in the used currency units.
 *
 * @param int $price Price.
 *
 * @return float
 */
function almapay_wc_price_from_cents( $price ) {
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
function almapay_wc_format_percent_from_bps( $bps ) {
	$decimal_separator  = wc_get_price_decimal_separator();
	$thousand_separator = wc_get_price_thousand_separator();
	$decimals           = wc_get_price_decimals();
	$price_format       = get_woocommerce_price_format();
	$negative           = $bps < 0;
	$bps                = number_format( almapay_wc_price_from_cents( $bps ), $decimals, $decimal_separator, $thousand_separator );
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
function almapay_wc_format_price_from_cents( $price, $args = array() ) {
	return wc_price( almapay_wc_price_from_cents( $price ), array_merge( array( 'currency' => 'EUR' ), $args ) );
}

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * Taken from WooCommerce, to which it was added in version 3.0.0, and we need support for older WC versions.
 *
 * @param string $string String to convert.
 * @return bool
 */
function almapay_wc_string_to_bool( $string ) {
	return is_bool( $string )
		? $string
		: ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
}
