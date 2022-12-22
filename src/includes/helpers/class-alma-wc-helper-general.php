<?php
/**
 * Alma_WC_Helper_General.
 *
 * @since 4.0.0
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Helper_General.
 */
class Alma_WC_Helper_General {

	/**
	 * Allow Alma domains for redirect.
	 *
	 * @param string[] $domains Whitelisted domains for `wp_safe_redirect`.
	 *
	 * @return string[]
	 */
	public function alma_domains_whitelist( $domains ) {
		$domains[] = 'pay.getalma.eu';
		$domains[] = 'pay.sandbox.getalma.eu';

		return $domains;
	}

	/**
	 * Returns the list of texts proposed to be displayed on front-office.
	 *
	 * @return array
	 */
	public static function get_display_texts_keys_and_values() {
		return array(
			'at_shipping' => __( 'At shipping', 'alma-gateway-for-woocommerce' ),
		);
	}

}


