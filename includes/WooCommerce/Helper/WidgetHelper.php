<?php

namespace Alma\Gateway\WooCommerce\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class WidgetHelper {

	/**
	 * Display the cart widget with the given price.
	 *
	 * @param int $price The total price of the cart in cents.
	 */
	public static function display_cart_widget( int $price ) {

		ShortcodeWidgetHelper::init_cart_shortcode( $price );
		ShortcodeWidgetHelper::display_default_cart_widget();
	}

	/**
	 * Display the product widget with the given price.
	 *
	 * @param int $price The price of the product in cents.
	 */
	public static function display_product_widget( int $price ) {
		ShortcodeWidgetHelper::init_product_shortcode( $price );
		ShortcodeWidgetHelper::display_default_product_widget();
	}
}
