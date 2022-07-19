<?php
/**
 * Alma settings
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Alma_WC_Settings_Helper
 *
 * Helps Settings to define defaults values (including in i18n context)
 */
class Alma_WC_Settings_Helper {

	/**
	 * Gets the default CSS selector for the price element of variable products, depending on the version of
	 * WooCommerce, as WooCommerce 4.4.0 added a `<bdi>` wrapper around the price.
	 *
	 * @return string
	 */
	public static function default_variable_price_selector() {
		$selector = 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount';
		if ( version_compare( wc()->version, '4.4.0', '>=' ) ) {
			$selector .= ' bdi';
		}

		return $selector;
	}

	/**
	 * Gets the default CSS selector for the price element of variable sale products, depending on the version of
	 * WooCommerce, as WooCommerce 4.4.0 added a `<bdi>` wrapper around the price.
	 *
	 * @return string
	 */
	public static function default_variable_sale_price_selector() {
		$selector = 'form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount';
		if ( version_compare( wc()->version, '4.4.0', '>=' ) ) {
			$selector .= ' bdi';
		}

		return $selector;
	}

	/**
	 * Gets the default title for pnx plus 4 payment method.
	 *
	 * @return string
	 */
	public static function default_pnx_plus_4_title() {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {

			return 'Spread your payments with Alma';
		}

		return __( 'Spread your payments with Alma', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pay later payment method.
	 *
	 * @return string
	 */
	public static function default_pay_later_title() {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {

			return 'Buy now, Pay later with Alma';
		}

		return __( 'Buy now, Pay later with Alma', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pnx payment method.
	 *
	 * @return string
	 */
	public static function default_pnx_title() {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {

			return 'Pay in installments with Alma';
		}

		return __( 'Pay in installments with Alma', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default description for all payment methods (pnx, pnx+4, pay later).
	 *
	 * @return string
	 */
	public static function default_payment_description() {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {

			return 'Fast and secure payment by credit card';
		}

		return __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default message when cart is not eligible.
	 *
	 * @return string
	 */
	public static function default_not_eligible_cart_message() {
		if ( Alma_WC_Internationalization::is_site_multilingual() ) {

			return 'Some products cannot be paid with monthly or deferred installments';
		}

		return __( 'Some products cannot be paid with monthly or deferred installments', 'alma-gateway-for-woocommerce' );
	}

}
