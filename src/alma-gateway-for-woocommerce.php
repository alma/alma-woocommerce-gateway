<?php
/**
 * Plugin Name: Alma - Pay in installments or later for wooCommerce
 * Plugin URI: https://docs.getalma.eu/docs/woocommerce
 * Description: Install Alma and boost your sales! It's simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.
 * Version: 2.6.0
 * Author: Alma
 * Author URI: https://www.getalma.eu
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: alma-gateway-for-woocommerce
 * Domain Path: /languages
 *
 * @package Alma_WooCommerce_Gateway
 *
 * WC requires at least: 2.6
 * WC tested up to: 6.4
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

if ( ! defined( 'ALMA_WC_VERSION' ) ) {
	define( 'ALMA_WC_VERSION', '2.6.0' );
}
if ( ! defined( 'ALMA_WC_PLUGIN_FILE' ) ) {
	define( 'ALMA_WC_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ALMA_WC_PLUGIN_BASENAME' ) ) {
	define( 'ALMA_WC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ALMA_WC_PLUGIN_URL' ) ) {
	define( 'ALMA_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Return instance of Alma_Plugin.
 *
 * @return Alma_WC_Plugin
 * @noinspection PhpIncludeInspection
 */
function alma_wc_plugin() {
	static $plugin;

	if ( ! isset( $plugin ) ) {
		require_once ALMA_WC_PLUGIN_PATH . 'includes/alma-wc-functions.php';
		require_once ALMA_WC_PLUGIN_PATH . 'vendor/autoload.php';
		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-autoloader.php';

		Alma_WC_Autoloader::autoload();
		$plugin = new Alma_WC_Plugin();
	}

	return $plugin;
}

alma_wc_plugin()->try_running();
