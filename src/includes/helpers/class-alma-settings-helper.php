<?php
/**
 * Alma_Settings_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Alma_Settings_Helper
 *
 * Helps Settings to define defaults values (including in i18n context)
 */
class Alma_Settings_Helper {



	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'enabled'                                    => 'yes',
			'payment_upon_trigger_enabled'               => 'no',
			'payment_upon_trigger_event'                 => 'completed',
			'payment_upon_trigger_display_text'          => 'at_shipping',
			'selected_fee_plan'                          => Alma_Constants_Helper::DEFAULT_FEE_PLAN,
			'enabled_general_3_0_0'                      => 'yes',
			'title_pay_with_alma'                        => self::default_pay_with_alma(),
			'title_pay_with_alma_in_page'                => self::default_pay_with_alma_in_page(),
			'description_gateway_description_in_page'    => self::default_pay_with_alma_in_page_description(),
			'title_payment_method_pnx'                   => self::default_pnx_title(),
			'description_payment_method_pnx'             => self::default_payment_description(),
			'title_payment_method_pay_now'               => self::default_pay_now_title(),
			'description_payment_method_pay_now'         => self::default_pay_now_description(),
			'title_payment_method_pay_later'             => self::default_pay_later_title(),
			'description_payment_method_pay_later'       => self::default_payment_description(),
			'title_payment_method_pnx_plus_4'            => self::default_pnx_plus_4_title(),
			'description_payment_method_pnx_plus_4'      => self::default_payment_description(),
			'display_cart_eligibility'                   => 'yes',
			'display_product_eligibility'                => 'yes',
			'variable_product_price_query_selector'      => self::default_variable_price_selector(),
			'variable_product_sale_price_query_selector' => self::default_variable_sale_price_selector(),
			'variable_product_check_variations_event'    => Alma_Constants_Helper::DEFAULT_CHECK_VARIATIONS_EVENT,
			'excluded_products_list'                     => array(),
			'cart_not_eligible_message_gift_cards'       => self::default_not_eligible_cart_message(),
			'live_api_key'                               => '',
			'test_api_key'                               => '',
			'environment'                                => 'test',
			'share_of_checkout_enabled'                  => 'no',
			'debug'                                      => 'yes',
			'keys_validity'                              => 'no',
			'display_in_page'                            => 'no',
		);
	}

	/**
	 * Gets the default title for the gatewxay
	 *
	 * @return string
	 */
	public static function default_pay_with_alma() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Pay with Alma';
		}
		return __( 'Pay with Alma', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for the gatewxay
	 *
	 * @return string
	 */
	public static function default_pay_with_alma_in_page() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Pay with Alma In Page';
		}
		return __( 'Pay with Alma In Page', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pnx payment method.
	 *
	 * @return string
	 */
	public static function default_pnx_title() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Pay in installments';
		}
		return __( 'Pay in installments', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pnx payment method.
	 *
	 * @return string
	 */
	public static function default_pay_now_title() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Pay now by credit card';
		}
		return __( 'Pay now by credit card', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default description for all payment methods (pnx, pnx+4, pay later).
	 *
	 * @return string
	 */
	public static function default_pay_now_description() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Fast and secured payments';
		}
		return __( 'Fast and secured payments', 'alma-gateway-for-woocommerce' );
	}


	/**
	 * Gets the default description for all payment methods (pnx, pnx+4, pay later).
	 *
	 * @return string
	 */
	public static function default_payment_description() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Fast and secure payment by credit card';
		}
		return __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default description for in page
	 *
	 * @return string
	 */
	public static function default_pay_with_alma_in_page_description() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Fast and secure payment by credit card *2 *3 *4';
		}
		return __( 'Fast and secure payment by credit card  *2 *3 *4', 'alma-gateway-for-woocommerce' );
	}



	/**
	 * Gets the default title for pay later payment method.
	 *
	 * @return string
	 */
	public static function default_pay_later_title() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Buy now, Pay later';
		}
		return __( 'Buy now, Pay later', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pnx plus 4 payment method.
	 *
	 * @return string
	 */
	public static function default_pnx_plus_4_title() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Spread your payments';
		}
		return __( 'Spread your payments', 'alma-gateway-for-woocommerce' );
	}

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
	 * Gets the default message when cart is not eligible.
	 *
	 * @return string
	 */
	public static function default_not_eligible_cart_message() {
		if ( Alma_Internationalization_Helper::is_site_multilingual() ) {
			return 'Some products cannot be paid with monthly or deferred installments';
		}
		return __( 'Some products cannot be paid with monthly or deferred installments', 'alma-gateway-for-woocommerce' );
	}

}
