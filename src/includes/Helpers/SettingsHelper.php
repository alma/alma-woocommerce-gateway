<?php
/**
 * SettingsHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\Exceptions\NoCredentialsException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsHelper
 *
 * Helps AlmaSettings to define defaults values (including in i18n context)
 */
class SettingsHelper {

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
			'selected_fee_plan'                          => ConstantsHelper::DEFAULT_FEE_PLAN,
			'enabled_general_3_0_0'                      => 'yes',
			'title_alma_in_page'                         => self::default_pnx_title(),
			'description_alma_in_page'                   => self::default_payment_description(),
			'title_alma_in_page_pay_now'                 => self::default_pay_now_title(),
			'description_alma_in_page_pay_now'           => self::default_description(),
			'title_alma_in_page_pay_later'               => self::default_pay_later_title(),
			'description_alma_in_page_pay_later'         => self::default_payment_description(),
			'title_alma'                                 => self::default_pnx_title(),
			'description_alma'                           => self::default_payment_description(),
			'title_alma_pay_now'                         => self::default_pay_now_title(),
			'description_alma_pay_now'                   => self::default_description(),
			'title_alma_pay_later'                       => self::default_pay_later_title(),
			'description_alma_pay_later'                 => self::default_payment_description(),
			'title_alma_pnx_plus_4'                      => self::default_pnx_plus_4_title(),
			'description_alma_pnx_plus_4'                => self::default_payment_description(),
			'title_blocks_alma_in_page'                  => self::default_pnx_title(),
			'description_blocks_alma_in_page'            => self::default_payment_description(),
			'title_blocks_alma_in_page_pay_now'          => self::default_pay_now_title(),
			'description_blocks_alma_in_page_pay_now'    => self::default_description(),
			'title_blocks_alma_in_page_pay_later'        => self::default_pay_later_title(),
			'description_blocks_alma_in_page_pay_later'  => self::default_payment_description(),
			'title_blocks_alma'                          => self::default_pnx_title(),
			'description_blocks_alma'                    => self::default_payment_description(),
			'title_blocks_alma_pay_now'                  => self::default_pay_now_title(),
			'description_blocks_alma_pay_now'            => self::default_description(),
			'title_blocks_alma_pay_later'                => self::default_pay_later_title(),
			'description_blocks_alma_pay_later'          => self::default_payment_description(),
			'title_blocks_alma_pnx_plus_4'               => self::default_pnx_plus_4_title(),
			'description_blocks_alma_pnx_plus_4'         => self::default_payment_description(),
			'display_cart_eligibility'                   => 'yes',
			'display_product_eligibility'                => 'yes',
			'variable_product_price_query_selector'      => self::default_variable_price_selector(),
			'variable_product_sale_price_query_selector' => self::default_variable_sale_price_selector(),
			'variable_product_check_variations_event'    => ConstantsHelper::DEFAULT_CHECK_VARIATIONS_EVENT,
			'excluded_products_list'                     => array(),
			'cart_not_eligible_message_gift_cards'       => self::default_not_eligible_cart_message(),
			'live_api_key'                               => '',
			'test_api_key'                               => '',
			'environment'                                => 'test',
			'share_of_checkout_enabled'                  => 'no',
			'debug'                                      => 'yes',
			'keys_validity'                              => 'no',
			'display_in_page'                            => 'no',
			'use_blocks_template'                        => 'no',
		);
	}


	/**
	 * Gets the default title for pnx payment method.
	 *
	 * @return string
	 */
	public static function default_pnx_title() {
		if ( InternationalizationHelper::is_site_multilingual() ) {
			return ConstantsHelper::PAY_IN_INSTALLMENTS;
		}
		return __( 'Pay in installments', 'alma-gateway-for-woocommerce' );
	}


	/**
	 * Gets the default title for pnx payment method.
	 *
	 * @return string
	 */
	public static function default_pay_now_title() {
		if ( InternationalizationHelper::is_site_multilingual() ) {
			return ConstantsHelper::PAY_NOW;
		}
		return __( 'Pay by credit card', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default description for all payment methods (pnx, pnx+4, pay later).
	 *
	 * @return string
	 */
	public static function default_description() {
		if ( InternationalizationHelper::is_site_multilingual() ) {
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
		if ( InternationalizationHelper::is_site_multilingual() ) {
			return 'Fast and secure payment by credit card';
		}
		return __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' );
	}


	/**
	 * Gets the default title for pay later payment method.
	 *
	 * @return string
	 */
	public static function default_pay_later_title() {
		if ( InternationalizationHelper::is_site_multilingual() ) {
			return ConstantsHelper::PAY_LATER;
		}
		return __( 'Pay later', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pnx plus 4 payment method.
	 *
	 * @return string
	 */
	public static function default_pnx_plus_4_title() {
		if ( InternationalizationHelper::is_site_multilingual() ) {
			return ConstantsHelper::PAY_BY_FINANCING;
		}
		return __( 'Pay with financing', 'alma-gateway-for-woocommerce' );
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
		if ( InternationalizationHelper::is_site_multilingual() ) {
			return 'Some products cannot be paid with monthly or deferred installments';
		}
		return __( 'Some products cannot be paid with monthly or deferred installments', 'alma-gateway-for-woocommerce' );
	}

	/**
	 *  Reset min and max amount for all plans.
	 *
	 * @param array $alma_settings The settings.
	 *
	 * @return mixed
	 */
	public static function reset_plans( $alma_settings ) {
		foreach ( array_keys( $alma_settings ) as $key ) {
			if ( ToolsHelper::is_amount_plan_key( $key ) ) {
				$alma_settings[ $key ] = null;
			}
		}

		$alma_settings['allowed_fee_plans'] = null;
		$alma_settings['live_merchant_id']  = null;
		$alma_settings['test_merchant_id']  = null;

		return $alma_settings;
	}

	/**
	 * Checks the api keys
	 *
	 * @param boolean $has_keys Keys.
	 * @param bool    $throw_exception Do we want to throw the exception.
	 *
	 * @return void
	 * @throws NoCredentialsException The exception.
	 */
	public static function check_alma_keys( $has_keys, $throw_exception = true ) {
		// Do we have keys for the environment?
		if ( ! $has_keys ) { // nope.
			$message = sprintf(
			// translators: %s: Admin settings url.
				__( 'Alma is almost ready. To get started, <a href="%s">fill in your API keys</a>.', 'alma-gateway-for-woocommerce' ),
				esc_url( AssetsHelper::get_admin_setting_url() )
			);

			alma_plugin()->admin_notices->add_admin_notice( 'no_alma_keys', 'notice notice-warning', $message );

			if ( $throw_exception ) {
				throw new NoCredentialsException( $message );
			}
		}
	}

}
