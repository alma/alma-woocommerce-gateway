<?php
/**
 * Plugin Name: Alma - Pay in installments or later for WooCommerce
 * Plugin URI: https://docs.almapay.com/docs/woocommerce
 * Description: Install Alma and boost your sales! It's simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.
 * Version: 5.2.0
 * Author: Alma
 * Author URI: https://almapay.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: alma-gateway-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 4.4
 * Requires PHP: 5.6
 * Tested up to: 6.3.1
 *
 * @package Alma_Gateway_For_Woocommerce
 *
 * WC requires at least: 2.6
 * WC tested up to: 8.1.1
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

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! defined( 'ALMA_VERSION' ) ) {
	define( 'ALMA_VERSION', '5.2.0' );
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

// Define a constant to see if site is network activated.
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	// Makes sure the plugin is defined before trying to use it.
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

if ( is_plugin_active_for_network( 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php' ) ) {
	define( 'ALMA_PLUGIN_NETWORK_ACTIVATED', true );
} else {
	define( 'ALMA_PLUGIN_NETWORK_ACTIVATED', false );
}

/**
 * Retrieve an option value from the current network or site.
 *
 * @param string $option Name of the option to retrieve.
 * @param mixed  $default The default value.
 * @param bool   $deprecated Wheter to use cache.
 *
 * @return false|mixed|null
 */
function alma_get_option( $option, $default = false, $deprecated = true ) {

	if ( ALMA_PLUGIN_NETWORK_ACTIVATED ) {
		// Get network site option.
		return get_site_option( $option, $default, $deprecated );
	}
	// Get site option.
	return get_option( $option, $default );
}

/**
 * Update an existing option.
 *
 * @param string  $option Name of the option to retrieve.
 * @param mixed   $value The value.
 * @param boolean $autoload Autoload.
 *
 * @return bool
 */
function alma_update_option( $option, $value, $autoload = null ) {

	if ( ALMA_PLUGIN_NETWORK_ACTIVATED ) {
		// Get network site option.
		return update_site_option( $option, $value );
	}
	// Get site option.
	return update_option( $option, $value, $autoload );
}

/**
 * Delete an option.
 *
 * @param string $option Name of the option to delete.
 *
 * @return bool
 */
function alma_delete_option( $option ) {

	if ( ALMA_PLUGIN_NETWORK_ACTIVATED ) {
		// Get network site option.
		return delete_site_option( $option );
	}
	// Get site option.
	return delete_option( $option );
}

/**
 * Add a new option.
 *
 * @param string $option Name of the option to retrieve.
 * @param mixed  $value The value.
 *
 * @return bool
 */
function alma_add_option( $option, $value ) {

	if ( ALMA_PLUGIN_NETWORK_ACTIVATED ) {
		// Get network site option.
		return add_site_option( $option, $value );
	}
	// Get site option.
	return add_option( $option, $value );
}

// Make sure WooCommerce is active.
if (
	(
		is_multisite()
		&& ! is_plugin_active( 'woocommerce/woocommerce.php' )
	)
	||
	(
		! is_multisite()
		&& ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	)
) {
	return;
}

require_once ALMA_PLUGIN_PATH . 'vendor/autoload.php';

// Load the autoloader from its own file.
require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Return instance of Alma_Plugin.
 *
 * @return  Alma\Woocommerce\Alma_Plugin
 * @noinspection PhpIncludeInspection
 */
function alma_plugin() {
	static $plugin;

	if ( ! isset( $plugin ) ) {

		$plugin = Alma\Woocommerce\Alma_Plugin::get_instance();

	}

	return $plugin;

}

/**
 * Add the plugin.
 */
add_action( 'plugins_loaded', 'alma_plugin' );




