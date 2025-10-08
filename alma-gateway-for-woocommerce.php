<?php
/**
 * Plugin Name: Alma - Pay in installments or later for WooCommerce
 * Plugin URI: https://docs.almapay.com/docs/woocommerce
 * Description: Install Alma and boost your sales! It's simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.
 * Author: Alma
 * Version: 6.0.0-poc
 * Author URI: https://almapay.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: alma-gateway-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.2.0
 * Requires PHP: 7.4
 * Tested up to: 6.8.1
 *
 * @package Alma_Gateway_For_Woocommerce
 *
 * WC requires at least: 6.2.0
 * WC tested up to: 9.8.5
 *
 * Alma Payment Gateway for WooCommerce is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Alma Payment Gateway for WooCommerce is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Alma Payment Gateway for WooCommerce. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
 */

require_once 'vendor/autoload.php';
require_once 'includes/functions.php';

use Alma\Gateway\Infrastructure\Block\Checkout\CreditCheckoutBlock;
use Alma\Gateway\Infrastructure\Block\Checkout\PayLaterCheckoutBlock;
use Alma\Gateway\Infrastructure\Block\Checkout\PayNowCheckoutBlock;
use Alma\Gateway\Infrastructure\Block\Checkout\PnxCheckoutBlock;
use Alma\Gateway\Infrastructure\Block\Widget\WidgetBlock;
use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

$alma_gateway_plugin = Plugin::get_instance();

add_action(
	'plugins_loaded',
	array( $alma_gateway_plugin, 'plugin_warmup' ),
	0
);

add_action(
	'plugins_loaded',
	array( $alma_gateway_plugin, 'plugin_setup' )
);

/**
 * Init custom_order_tables if available in Woocommerce version.
 */
add_action(
	'before_woocommerce_init',
	function () {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
);

add_action(
	'init',
	function () {
		register_block_type_from_metadata( __DIR__ . '/build/alma-widget-block' );
	}
);

/**
 * Register the Alma widget block.
 */
add_action(
	'woocommerce_blocks_loaded',
	function () {
		add_action(
			'woocommerce_blocks_cart_block_registration',
			function ( $integration_registry ) {
				$alma_widget_block = Plugin::get_container()->get( WidgetBlock::class );

				$integration_registry->register( $alma_widget_block );
			}
		);
	}
);

add_action(
	'woocommerce_blocks_payment_method_type_registration',
	function ( PaymentMethodRegistry $payment_method_registry ) {
		// Register an instance of Alma_Gateway_Blocks.
		$payment_method_registry->register( Plugin::get_container()->get( PnxCheckoutBlock::class ) );
		$payment_method_registry->register( Plugin::get_container()->get( CreditCheckoutBlock::class ) );
		$payment_method_registry->register( Plugin::get_container()->get( PayLaterCheckoutBlock::class ) );
		$payment_method_registry->register( Plugin::get_container()->get( PayNowCheckoutBlock::class ) );
	}
);
