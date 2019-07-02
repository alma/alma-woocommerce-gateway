<?php
/**
 * Plugin Name: Alma Monthly Payments for WooCommerce
 * Plugin URI: https://www.getalma.eu/wordpress
 * Description: Easily provide monthly payments to your customers, risk-free!
 * Version: 1.1.1
 * Author: Alma
 * Author URI: https://www.getalma.eu
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: alma-woocommerce-gateway
 * Domain Path: /languages
 *
 * WC requires at least: 2.6
 * WC tested up to: 3.5
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
	die( 'Not allowed' );
}

define( 'ALMA_WC_VERSION', '1.1.1' );
define( 'ALMA_WC_TEXT_DOMAIN', 'alma-woocommerce-gateway' );

/**
 * Return instance of Alma_Plugin.
 *
 * @return Alma_WC_Plugin
 */
function alma_wc_plugin() {
	static $plugin;

	if ( ! isset( $plugin ) ) {
		require_once( 'includes/class-alma-wc-plugin.php' );

		$plugin = new Alma_WC_Plugin( __FILE__, ALMA_WC_VERSION );
	}

	return $plugin;
}

alma_wc_plugin()->try_running();
