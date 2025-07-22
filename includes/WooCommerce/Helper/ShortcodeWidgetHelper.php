<?php

namespace Alma\Gateway\WooCommerce\Helper;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class ShortcodeWidgetHelper {

	const CART_SHORTCODE_TAG    = 'alma-cart-eligibility';
	const PRODUCT_SHORTCODE_TAG = 'alma-product-eligibility';

	public static function display_default_cart_widget() {
		add_action(
			'woocommerce_after_cart',
			function () {
				echo do_shortcode( sprintf( '[%s]', self::CART_SHORTCODE_TAG ) );
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param int $price The total price of the cart in cents.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public static function init_cart_shortcode( $price ) {

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
		wp_localize_script(
			'alma-frontend-widget-implementation',
			'alma_widget_settings',
			array(
				'price' => $price,
			)
		);

		add_shortcode(
			self::CART_SHORTCODE_TAG,
			function ( $atts, $content = '' ) {

				$class         = isset( $atts['class'] ) ? htmlspecialchars( $atts['class'] ) : '';
				$style         = '';
				$debug_content = '';

				// Exemple : affichage d’un bouton ou d’un bloc custom
				return sprintf(
					'<div class="%s %s" style="%s">%s<div class="alma_widget">CODE SHORTCODE</div></div>',
					self::CART_SHORTCODE_TAG,
					$class,
					$style,
					$debug_content
				);
			}
		);
	}


	public static function display_default_product_widget() {
		add_action(
			'woocommerce_before_add_to_cart_form',
			function () {
				echo do_shortcode( sprintf( '[%s]', self::PRODUCT_SHORTCODE_TAG ) );
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param int $price The total price of the cart in cents.
	 *
	 * @return void
	 */
	public static function init_product_shortcode( $price ) {

		wp_enqueue_style(
			'alma-frontend-widget',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css'
		);
		wp_enqueue_script(
			'alma-frontend-widget',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js'
		);

		add_shortcode(
			self::PRODUCT_SHORTCODE_TAG,
			function ( $atts, $content = '' ) {

				$class         = isset( $atts['class'] ) ? htmlspecialchars( $atts['class'] ) : '';
				$style         = '';
				$debug_content = '';

				// Exemple : affichage d’un bouton ou d’un bloc custom
				return sprintf(
					'<div class="%s %s" style="%s">%s<div class="alma_wc_content">CODE SHORTCODE</div></div>',
					self::CART_SHORTCODE_TAG,
					$class,
					$style,
					$debug_content
				);
			}
		);
	}
}
