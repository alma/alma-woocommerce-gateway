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
use Alma\Woocommerce\Factories\PluginFactory;
use Alma\Woocommerce\Factories\VersionFactory;

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
	 * Internationalization Helper.
	 *
	 * @var InternationalizationHelper
	 */
	protected $internationalization_helper;

	/**
	 * Version Helper.
	 *
	 * @var VersionFactory
	 */
	protected $version_factory;

	/**
	 * Tools Helper.
	 *
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	/**
	 * Asset Helper.
	 *
	 * @var AssetsHelper
	 */
	protected $assets_helper;


	/**
	 * Plugin Factory.
	 *
	 * @var PluginFactory
	 */
	protected $plugin_factory;


	/**
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param InternationalizationHelper $internationalization_helper The internationalization helper.
	 * @param VersionFactory             $version_factory The version helper.
	 * @param ToolsHelper                $tools_helper The tools helper.
	 * @param AssetsHelper               $assets_helper The asset helper.
	 * @param PluginFactory              $plugin_factory The plugin factory.
	 */
	public function __construct( $internationalization_helper, $version_factory, $tools_helper, $assets_helper, $plugin_factory ) {
		$this->internationalization_helper = $internationalization_helper;
		$this->version_factory             = $version_factory;
		$this->tools_helper                = $tools_helper;
		$this->assets_helper               = $assets_helper;
		$this->plugin_factory              = $plugin_factory;
	}
	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public function default_settings() {
		return array(
			'enabled'                                    => 'yes',
			'payment_upon_trigger_enabled'               => 'no',
			'payment_upon_trigger_event'                 => 'completed',
			'payment_upon_trigger_display_text'          => 'at_shipping',
			'selected_fee_plan'                          => ConstantsHelper::DEFAULT_FEE_PLAN,
			'enabled_general_3_0_0'                      => 'yes',
			'title_alma_in_page'                         => $this->default_pnx_title(),
			'description_alma_in_page'                   => $this->default_payment_description(),
			'title_alma_in_page_pay_now'                 => $this->default_pay_now_title(),
			'description_alma_in_page_pay_now'           => $this->default_description(),
			'title_alma_in_page_pay_later'               => $this->default_pay_later_title(),
			'description_alma_in_page_pay_later'         => $this->default_payment_description(),
			'title_alma_in_page_pnx_plus_4'              => $this->default_pnx_plus_4_title(),
			'description_alma_in_page_pnx_plus_4'        => $this->default_payment_description(),
			'title_alma'                                 => $this->default_pnx_title(),
			'description_alma'                           => $this->default_payment_description(),
			'title_alma_pay_now'                         => $this->default_pay_now_title(),
			'description_alma_pay_now'                   => $this->default_description(),
			'title_alma_pay_later'                       => $this->default_pay_later_title(),
			'description_alma_pay_later'                 => $this->default_payment_description(),
			'title_alma_pnx_plus_4'                      => $this->default_pnx_plus_4_title(),
			'description_alma_pnx_plus_4'                => $this->default_payment_description(),
			'title_blocks_alma_in_page'                  => $this->default_pnx_title(),
			'description_blocks_alma_in_page'            => $this->default_payment_description(),
			'title_blocks_alma_in_page_pay_now'          => $this->default_pay_now_title(),
			'description_blocks_alma_in_page_pay_now'    => $this->default_description(),
			'title_blocks_alma_in_page_pay_later'        => $this->default_pay_later_title(),
			'description_blocks_alma_in_page_pay_later'  => $this->default_payment_description(),
			'title_blocks_alma'                          => $this->default_pnx_title(),
			'description_blocks_alma'                    => $this->default_payment_description(),
			'title_blocks_alma_pay_now'                  => $this->default_pay_now_title(),
			'description_blocks_alma_pay_now'            => $this->default_description(),
			'title_blocks_alma_pay_later'                => $this->default_pay_later_title(),
			'description_blocks_alma_pay_later'          => $this->default_payment_description(),
			'title_blocks_alma_pnx_plus_4'               => $this->default_pnx_plus_4_title(),
			'description_blocks_alma_pnx_plus_4'         => $this->default_payment_description(),
			'display_cart_eligibility'                   => 'yes',
			'display_product_eligibility'                => 'yes',
			'variable_product_price_query_selector'      => $this->default_variable_price_selector(),
			'variable_product_sale_price_query_selector' => $this->default_variable_sale_price_selector(),
			'variable_product_check_variations_event'    => ConstantsHelper::DEFAULT_CHECK_VARIATIONS_EVENT,
			'excluded_products_list'                     => array(),
			'cart_not_eligible_message_gift_cards'       => $this->default_not_eligible_cart_message(),
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
	public function default_pnx_title() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
			return ConstantsHelper::PAY_IN_INSTALLMENTS;
		}
		return __( 'Pay in installments', 'alma-gateway-for-woocommerce' );
	}


	/**
	 * Gets the default title for pnx payment method.
	 *
	 * @return string
	 */
	public function default_pay_now_title() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
			return ConstantsHelper::PAY_NOW;
		}
		return __( 'Pay by credit card', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default description for all payment methods (pnx, pnx+4, pay later).
	 *
	 * @return string
	 */
	public function default_description() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
			return 'Fast and secured payments';
		}
		return __( 'Fast and secured payments', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default description for all payment methods (pnx, pnx+4, pay later).
	 *
	 * @return string
	 */
	public function default_payment_description() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
			return 'Fast and secure payment by credit card';
		}
		return __( 'Fast and secure payment by credit card', 'alma-gateway-for-woocommerce' );
	}


	/**
	 * Gets the default title for pay later payment method.
	 *
	 * @return string
	 */
	public function default_pay_later_title() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
			return ConstantsHelper::PAY_LATER;
		}
		return __( 'Pay later', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Gets the default title for pnx plus 4 payment method.
	 *
	 * @return string
	 */
	public function default_pnx_plus_4_title() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
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
	public function default_variable_price_selector() {
		$selector = 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount';
		if ( version_compare( $this->version_factory->get_version(), '4.4.0', '>=' ) ) {
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
	public function default_variable_sale_price_selector() {
		$selector = 'form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount';
		if ( version_compare( $this->version_factory->get_version(), '4.4.0', '>=' ) ) {
			$selector .= ' bdi';
		}

		return $selector;
	}

	/**
	 * Gets the default message when cart is not eligible.
	 *
	 * @return string
	 */
	public function default_not_eligible_cart_message() {
		if ( $this->internationalization_helper->is_site_multilingual() ) {
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
	public function reset_plans( $alma_settings ) {
		foreach ( array_keys( $alma_settings ) as $key ) {
			if ( $this->tools_helper->is_amount_plan_key( $key ) ) {
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
	public function check_alma_keys( $has_keys, $throw_exception = true ) {
		// Do we have keys for the environment?
		if ( ! $has_keys ) { // nope.
			$message = sprintf(
			// translators: %s: Admin settings url.
				__( 'Alma is almost ready. To get started, <a href="%s">fill in your API keys</a>.', 'alma-gateway-for-woocommerce' ),
				esc_url( $this->assets_helper->get_admin_setting_url() )
			);

			$this->plugin_factory->add_admin_notice( 'no_alma_keys', 'notice notice-warning', $message );

			if ( $throw_exception ) {
				throw new NoCredentialsException( $message );
			}
		}
	}

}
