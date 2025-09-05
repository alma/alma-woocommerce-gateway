<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Helper;

use Alma\API\Entity\FeePlan;
use Alma\API\Entity\FeePlanList;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Helper\AssetsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Helper class to display the cart and product widgets with a shortcode.
 * This class manages the display for Classic themes.
 */
class ShortcodeWidgetHelper {

	/** @var string class used by merchant's shortcode to display widget */
	const WIDGET_CLASS = 'alma-widget';

	/** @var string Default class to display widget */
	const WIDGET_DEFAULT_CLASS = 'alma-default-widget';

	/** @var string Shortcode tag for the cart widget */
	const CART_SHORTCODE_TAG = 'alma-cart-eligibility';

	/** @var string Shortcode tag for the product widget */
	const PRODUCT_SHORTCODE_TAG = 'alma-product-eligibility';

	/**
	 * Display the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @return void
	 */
	public static function display_default_cart_widget() {
		add_action(
			'woocommerce_proceed_to_checkout',
			function () {
				echo do_shortcode( sprintf( '[%s class="%s"]', self::CART_SHORTCODE_TAG, self::WIDGET_DEFAULT_CLASS ) );
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string      $environment The API mode (live or test).
	 * @param string      $merchant_id The merchant ID.
	 * @param int         $price The total price of the cart in cents.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 * @param string      $language The language code (e.g., 'en', 'fr', etc.).
	 * @param bool        $display_widget Whether to display the widget or not.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public static function init_cart_shortcode( string $environment, string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language, bool $display_widget = false ) {
		if ( $display_widget ) {
			self::add_scripts_and_styles();
			self::add_parameters( $environment, $merchant_id, $price, $fee_plan_list, $language );
			self::add_shortcode( self::CART_SHORTCODE_TAG );
		} else {
			self::add_empty_shortcode( self::CART_SHORTCODE_TAG );
		}
	}

	/**
	 * Display the Alma product widget shortcode.
	 * This shortcode can be used to display the Alma product widget
	 *
	 * @return void
	 */
	public static function display_default_product_widget() {
		add_action(
			'woocommerce_before_add_to_cart_form',
			function () {
				echo do_shortcode(
					sprintf(
						'[%s class="%s"]',
						self::PRODUCT_SHORTCODE_TAG,
						self::WIDGET_DEFAULT_CLASS
					)
				);
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string      $environment The API mode (live or test).
	 * @param string      $merchant_id The merchant ID.
	 * @param int         $price The total price of the cart in cents.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 * @param string      $language The language code.
	 * @param bool        $display_widget Whether to display the widget or not.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public static function init_product_shortcode( string $environment, string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language, bool $display_widget = false ) {
		if ( $display_widget ) {
			self::add_scripts_and_styles();
			self::add_parameters( $environment, $merchant_id, $price, $fee_plan_list, $language );
			self::add_shortcode( self::PRODUCT_SHORTCODE_TAG );
		} else {
			self::add_empty_shortcode( self::PRODUCT_SHORTCODE_TAG );
		}
	}

	/**
	 * Enqueue the scripts and styles needed for the Alma widget.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	private static function add_scripts_and_styles() {
		wp_enqueue_style(
			'alma-frontend-widget-cdn',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css'
		);
		wp_enqueue_script(
			'alma-frontend-widget-cdn',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js'
		);
		wp_enqueue_script(
			'alma-frontend-widget-implementation',
			( new AssetsHelper() )->get_asset_url( 'js/frontend/alma-frontend-widget-implementation.js' ),
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	/**
	 * Add the parameters needed for the Alma widget.
	 *
	 * @param string      $environment The API environment (live or test).
	 * @param string      $merchant_id The merchant ID.
	 * @param int         $price The price of the product or cart in cents.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 * @param string      $language The language code.
	 *
	 * @return void
	 * @see assets/js/frontend/alma-frontend-widget-implementation.js
	 */
	private static function add_parameters( string $environment, string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language ) {
		wp_localize_script(
			'alma-frontend-widget-implementation',
			'alma_widget_settings',
			array(
				'environment'             => $environment,
				'widget_selector'         => sprintf( '.%s', self::WIDGET_CLASS ),
				'widget_default_selector' => sprintf( '.%s', self::WIDGET_DEFAULT_CLASS ),
				'merchant_id'             => $merchant_id,
				'price'                   => $price,
				'language'                => $language,
				'fee_plan_list'           => array_map(
					function ( FeePlan $plan ) {
						return array(
							'installmentsCount' => $plan->getInstallmentsCount(),
							'minAmount'         => $plan->getMinPurchaseAmount( true ),
							'maxAmount'         => $plan->getMaxPurchaseAmount( true ),
							'deferredDays'      => $plan->getDeferredDays(),
							'deferredMonths'    => $plan->getDeferredMonths(),
						);
					},
					$fee_plan_list->getArrayCopy()
				),
				'hide_if_not_eligible'    => false,
				'transition_delay'        => 5500,
				'monochrome'              => true,
				'hide_border'             => false,
			)
		);
	}

	/**
	 * Add the shortcode for the Alma widget.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return void
	 */
	private static function add_shortcode( string $tag ) {
		add_shortcode(
			$tag,
			function ( $atts ) use ( $tag ) {

				$class = isset( $atts['class'] ) ? htmlspecialchars( $atts['class'] ) : self::WIDGET_CLASS;
				$style = '';

				return sprintf(
					'<div class="%s %s" style="%s"></div>',
					$tag,
					$class,
					$style
				);
			}
		);
	}

	/**
	 * Add an empty shortcode that returns a div with the given tag.
	 * That's used when the widget is not enabled, to avoid breaking the layout.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return void
	 */
	private static function add_empty_shortcode( string $tag ) {
		add_shortcode(
			$tag,
			function () use ( $tag ) {
				return sprintf(
					'<div class="%s"></div>',
					$tag,
				);
			}
		);
	}
}
