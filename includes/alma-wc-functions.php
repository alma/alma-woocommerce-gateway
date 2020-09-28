<?php
/** HELPER FUNCTIONS */

/**
 * Converts a float price to its integer cents value, used by the API
 *
 * @param $price float
 *
 * @return integer
 */
function alma_wc_price_to_cents( $price ) {
	return (int) ( round( $price * 100 ) );
}

/**
 * Converts an integer price in cents to a float price in the used currency units
 *
 * @param $price int
 *
 * @return float
 */
function alma_wc_price_from_cents( $price ) {
	return (float) ( $price / 100 );
}

// https://secure.php.net/manual/en/function.array-merge-recursive.php#104145
// Addition: will automatically discard null values
function alma_wc_array_merge_recursive() {

	if ( func_num_args() < 2 ) {
		trigger_error( __FUNCTION__ . ' needs two or more array arguments', E_USER_WARNING );

		return null;
	}
	$arrays = func_get_args();
	$merged = array();
	while ( $arrays ) {
		$array = array_shift( $arrays );
		if ( $array === null ) {
			continue;
		}
		if ( ! is_array( $array ) ) {
			trigger_error( __FUNCTION__ . ' encountered a non array argument', E_USER_WARNING );

			return null;
		}
		if ( ! $array ) {
			continue;
		}
		foreach ( $array as $key => $value ) {
			if ( is_string( $key ) ) {
				if ( is_array( $value ) && array_key_exists( $key, $merged ) && is_array( $merged[ $key ] ) ) {
					$merged[ $key ] = call_user_func( __FUNCTION__, $merged[ $key ], $value );
				} else {
					$merged[ $key ] = $value;
				}
			} else {
				$merged[] = $value;
			}
		}
	}

	return $merged;
}


/**
 * Checks if the current request is a WP REST API request.
 *
 * Case #1: After WP_REST_Request initialisation
 * Case #2: Support "plain" permalink settings
 * Case #3: It can happen that WP_Rewrite is not yet initialized,
 *          so do this (wp-settings.php)
 * Case #4: URL Path begins with wp-json/ (your REST prefix)
 *          Also supports WP installations in subfolders
 *
 * @returns boolean
 * @author matzeeable
 * @see https://wordpress.stackexchange.com/a/317041
 */
function alma_wc_is_rest_call() {
	$prefix = rest_get_url_prefix();
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
		|| isset( $_GET['rest_route'] ) // (#2)
		&& strpos( trim( $_GET['rest_route'], '\\/' ), $prefix, 0 ) === 0 ) {
		return true;
	}
	// (#3)
	global $wp_rewrite;
	if ( $wp_rewrite === null ) {
		$wp_rewrite = new WP_Rewrite();
	}

	// (#4)
	$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
	$current_url = wp_parse_url( add_query_arg( array() ) );
	return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
}
