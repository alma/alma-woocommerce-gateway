<?php

namespace Alma\Gateway\WooCommerce\Helper;

use Alma\API\Entities\FeePlanList;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class ShortcodeWidgetHelper {

	/** @var string class used by merchant's shortcode to display widget */
	const WIDGET_CLASS = 'alma_widget';

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
			'woocommerce_after_cart',
			function () {
				echo do_shortcode( sprintf( '[%s class="%s"]', self::CART_SHORTCODE_TAG, self::WIDGET_DEFAULT_CLASS ) );
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string $merchant_id The merchant ID.
	 * @param int    $price The total price of the cart in cents.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public static function init_cart_shortcode( string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language ) {

		self::add_scripts_and_styles();
		self::add_parameters( $merchant_id, $price, $fee_plan_list, $language );
		self::add_shortcode( self::CART_SHORTCODE_TAG );
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
				echo do_shortcode( sprintf( '[%s class="%s"]', self::PRODUCT_SHORTCODE_TAG, self::WIDGET_DEFAULT_CLASS ) );
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string $merchant_id The merchant ID.
	 * @param int    $price The total price of the cart in cents.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public static function init_product_shortcode( string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language ) {

		self::add_scripts_and_styles();
		self::add_parameters( $merchant_id, $price, $fee_plan_list, $language );
		self::add_shortcode( self::PRODUCT_SHORTCODE_TAG );
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
			( new AssetsHelper() )->get_asset_url( 'js/alma-frontend-widget-implementation.js' ),
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	/**
	 * Add the parameters needed for the Alma widget.
	 *
	 * @param string      $merchant_id The merchant ID.
	 * @param int         $price The price of the product or cart in cents.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 * @param string      $language The language code.
	 *
	 * @return void
	 */
	private static function add_parameters( string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language ) {
		wp_localize_script(
			'alma-frontend-widget-implementation',
			'alma_widget_settings',
			array(
				'widget_selector'         => sprintf( '.%s', self::WIDGET_CLASS ),
				'widget_default_selector' => sprintf( '.%s', self::WIDGET_DEFAULT_CLASS ),
				'merchant_id'             => $merchant_id,
				'price'                   => $price,
				'language'                => $language,
				'fee_plan_list'           => array_map(
					function ( $plan ) {
						return array(
							'installmentsCount' => $plan->getInstallmentsCount(),
							'minAmount'         => $plan->getMinPurchaseAmount( true ),
							'maxAmount'         => $plan->getMaxPurchaseAmount( true ),
						);
					},
					$fee_plan_list->getIterator()->getArrayCopy()
				),
				'hide_if_not_eligible'    => false,
				'transition_delay'        => 5500,
				'monochrome'              => false,
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
			function ( $atts, $content = '' ) use ( $tag ) {

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
}
