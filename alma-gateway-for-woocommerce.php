<?php
/**
 * Plugin Name: Alma - Pay in installments or later for WooCommerce
 * Plugin URI: https://docs.almapay.com/docs/woocommerce
 * Description: Install Alma and boost your sales! It's simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.
 * Version: 5.14.0
 * Author: Alma
 * Author URI: https://almapay.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: alma-gateway-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 4.4
 * Requires PHP: 5.6
 * Tested up to: 6.8.2
 *
 * @package Alma_Gateway_For_Woocommerce
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 10.0.4
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
use Alma\Woocommerce\Blocks\AlmaWidgetBlock;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! defined( 'ALMA_VERSION' ) ) {
	define( 'ALMA_VERSION', '5.14.0' );
}
if ( ! defined( 'ALMA_PLUGIN_FILE' ) ) {
	define( 'ALMA_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ALMA_PLUGIN_PATH' ) ) {
	define( 'ALMA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ALMA_PLUGIN_URL' ) ) {
	define( 'ALMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Make sure WooCommerce is active.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	return;
}


require_once ALMA_PLUGIN_PATH . 'vendor/autoload.php';

/**
 * Return instance of AlmaPlugin.
 *
 * @return  Alma\Woocommerce\AlmaPlugin
 * @noinspection PhpIncludeInspection
 */


function alma_plugin() {

	load_plugin_textdomain(
		'alma-gateway-for-woocommerce',
		false,
		plugin_basename( ALMA_PLUGIN_PATH ) . '/languages'
	);

	static $plugin;

	if ( ! isset( $plugin ) ) {
		$plugin = Alma\Woocommerce\AlmaPlugin::get_instance();
	}

	return $plugin;
}

/** Add the plugin. */
add_action( 'plugins_loaded', 'alma_plugin' );

/**
 * Init custom_order_tables if available in Woocommerce version.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			/**
			 * Skip WC class check.
			 *
			 * @psalm-suppress UndefinedClass
			 */
			/** @noinspection PhpFullyQualifiedNameUsageInspection */
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );// phpcs:ignore
			/** @noinspection PhpFullyQualifiedNameUsageInspection */
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );// phpcs:ignore
		}
	}
);

add_action(
	'init',
	function () {
		register_block_type_from_metadata( __DIR__ . '/build/alma-widget-block' );
		$alma_business_event_service = new \Alma\Woocommerce\Services\AlmaBusinessEventService();
		$alma_business_event_service->add_hooks();
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
				$integration_registry->register( new AlmaWidgetBlock() );
			}
		);
	}
);
