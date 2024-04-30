<?php
/**
 * PriceFactory.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Factories
 * @namespace Alma\Woocommerce\Factories
 */

namespace Alma\Woocommerce\Factories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PriceFactory
 */
class PriceFactory {

	/**
	 * Get Woocommerce decimal separator.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function get_woo_decimal_separator() {
		return wc_get_price_decimal_separator();
	}

	/**
	 * Get Woocommerce thousand separator.
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function get_woo_thousand_separator() {
		return wc_get_price_thousand_separator();
	}

	/**
	 *  Get Woocommerce decimals
	 *
	 * @codeCoverageIgnore
	 * @return int
	 */
	public function get_woo_decimals() {
		return wc_get_price_decimals();
	}

	/**
	 *  Get Woocommerce format
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function get_woo_format() {
		return get_woocommerce_price_format();
	}
}
