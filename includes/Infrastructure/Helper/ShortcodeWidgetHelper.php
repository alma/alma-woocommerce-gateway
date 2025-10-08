<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Helper class to display the cart and product widgets with a shortcode.
 * This class manages the display for Classic themes.
 */
class ShortcodeWidgetHelper {

	/** @var string Shortcode tag for the cart widget */
	const CART_SHORTCODE_TAG = 'alma-cart-eligibility';

	/** @var string Shortcode tag for the product widget */
	const PRODUCT_SHORTCODE_TAG = 'alma-product-eligibility';

	/**
	 * Display the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param string $widgetDefaultClass
	 *
	 * @return void
	 */
	public function displayDefaultCartWidget( $widgetDefaultClass ) {
		add_action(
			'woocommerce_proceed_to_checkout',
			function () use ( $widgetDefaultClass ) {
				echo do_shortcode(
					sprintf( '[%s class="%s"]', self::CART_SHORTCODE_TAG, $widgetDefaultClass )
				);
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param bool $displayWidget Whether to display the widget or not.
	 *
	 * @return void
	 */
	public function initCartShortcode( string $widgetClass, bool $displayWidget = false ) {
		if ( $displayWidget ) {
			$this->addShortcode( self::CART_SHORTCODE_TAG, $widgetClass );
		} else {
			$this->addEmptyShortcode( self::CART_SHORTCODE_TAG );
		}
	}

	/**
	 * Display the Alma product widget shortcode.
	 * This shortcode can be used to display the Alma product widget
	 *
	 * @return void
	 */
	public function displayDefaultProductWidget( string $widgetDefaultClass ) {
		add_action(
			'woocommerce_before_add_to_cart_form',
			function () use ( $widgetDefaultClass ) {
				echo do_shortcode(
					sprintf( '[%s class="%s"]', self::PRODUCT_SHORTCODE_TAG, $widgetDefaultClass )
				);
			}
		);
	}

	/**
	 * Create the Alma cart widget shortcode.
	 * This shortcode can be used to display the Alma cart widget
	 *
	 * @param bool   $displayWidget Whether to display the widget or not.
	 * @param string $widgetClass
	 *
	 * @return void
	 */
	public function initProductShortcode( string $widgetClass, bool $displayWidget = false ) {
		if ( $displayWidget ) {
			$this->addShortcode( self::PRODUCT_SHORTCODE_TAG, $widgetClass );
		} else {
			$this->addEmptyShortcode( self::PRODUCT_SHORTCODE_TAG );
		}
	}

	/**
	 * Add the shortcode for the Alma widget.
	 *
	 * @param string $tag The shortcode tag.
	 *
	 * @return void
	 */
	private function addShortcode( string $tag, string $widgetClass ) {
		add_shortcode(
			$tag,
			function ( $atts ) use ( $tag, $widgetClass ) {

				$class = isset( $atts['class'] ) ? htmlspecialchars( $atts['class'] ) : $widgetClass;
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
	private function addEmptyShortcode( string $tag ) {
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
