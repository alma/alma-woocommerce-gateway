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
 * Requires at least: 6.2
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
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! defined( 'ALMA_VERSION' ) ) {
	define( 'ALMA_VERSION', '6.0.0' );
}

$alma_gateway_plugin = Plugin::get_instance();

/**
 * Init custom_order_tables if available in Woocommerce version.
 */
add_action(
	'before_woocommerce_init',
	function () {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__ );
	}
);

// Plugin warmup: check prerequisites and init the DI container.
add_action(
	'plugins_loaded',
	array( $alma_gateway_plugin, 'plugin_warmup' ),
	0
);

// Plugin migration: run migrations if needed.
add_action(
	'plugins_loaded',
	array( $alma_gateway_plugin, 'plugin_migration' ),
	1
);

// Plugin setup: set up the plugin (register payment gateways, hooks, etc.) once WooCommerce is initialized.
add_action(
	'woocommerce_init',
	array( $alma_gateway_plugin, 'plugin_setup' ),
);
