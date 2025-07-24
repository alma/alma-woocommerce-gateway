<?php

namespace Alma\Gateway\WooCommerce\Helper;

use Alma\API\Entities\FeePlanList;
use Alma\Gateway\Business\Exception\ContainerException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * Helper class to display the cart and product widgets.
 * This class manages the display both for Classic and Block themes.
 */
class WidgetHelper {

	/**
	 * Display the cart widget with the given price.
	 *
	 * @param string      $environment The API environment (live or test).
	 * @param string      $merchant_id The merchant ID.
	 * @param int         $price The total price of the cart in cents.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 * @param string      $language The language code (e.g., 'en', 'fr', etc.).
	 *
	 * @throws ContainerException
	 */
	public static function display_cart_widget( string $environment, string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language ) {
		ShortcodeWidgetHelper::init_cart_shortcode( $environment, $merchant_id, $price, $fee_plan_list, $language );
		ShortcodeWidgetHelper::display_default_cart_widget();
	}

	/**
	 * Display the product widget with the given price.
	 *
	 * @param string      $environment The API environment (live or test).
	 * @param string      $merchant_id The merchant ID.
	 * @param int         $price The price of the product in cents.
	 * @param FeePlanList $fee_plan_list The list of fee plans.
	 * @param string      $language The language code (e.g., 'en', 'fr', etc.).
	 *
	 * @throws ContainerException
	 */
	public static function display_product_widget( string $environment, string $merchant_id, int $price, FeePlanList $fee_plan_list, string $language ) {
		ShortcodeWidgetHelper::init_product_shortcode( $environment, $merchant_id, $price, $fee_plan_list, $language );
		ShortcodeWidgetHelper::display_default_product_widget();
	}
}
