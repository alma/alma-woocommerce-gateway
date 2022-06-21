<?php
/**
 * Plugin Name: Alma - Pay in installments or later for WooCommerce
 * Plugin URI: https://docs.getalma.eu/docs/woocommerce
 * Description: Install Alma and boost your sales! It's simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.
 * Version: 3.0.0
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

if ( ! defined( 'ALMAPAY_WC_VERSION' ) ) {
	define( 'ALMAPAY_WC_VERSION', '3.0.0' );
}
if ( ! defined( 'ALMAPAY_WC_PLUGIN_FILE' ) ) {
	define( 'ALMAPAY_WC_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ALMAPAY_WC_PLUGIN_PATH' ) ) {
	define( 'ALMAPAY_WC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ALMAPAY_WC_PLUGIN_URL' ) ) {
	define( 'ALMAPAY_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Return instance of Alma_Plugin.
 *
 * @return Alma_WC_Plugin
 * @noinspection PhpIncludeInspection
 */
function almapay_wc_plugin() {
	static $plugin;

	if ( ! isset( $plugin ) ) {

		require_once ALMAPAY_WC_PLUGIN_PATH . 'includes/class-almapay-wc-upgrade-update-method.php';
		$upgrade_update_method = new Almapay_WC_Upgrade_update_method();
		register_activation_hook( __FILE__, array( $upgrade_update_method, 'new_alma_plugin_activation_hook' ) );

		require_once ALMAPAY_WC_PLUGIN_PATH . 'includes/almapay-wc-functions.php';
		require_once ALMAPAY_WC_PLUGIN_PATH . 'vendor/autoload.php';
		require_once ALMAPAY_WC_PLUGIN_PATH . 'includes/class-almapay-wc-autoloader.php';

		Almapay_WC_Autoloader::autoload();
		$plugin = new Almapay_WC_Plugin();
	}

	return $plugin;
}

almapay_wc_plugin()->try_running();

// $debug_tags = array();
// add_action( 'all', function ( $tag ) {
// global $debug_tags;
// if ( in_array( $tag, $debug_tags ) ) {
// return;
// }
// error_log( '------> action = ' . $tag );
// echo "<pre>" . $tag . "</pre>";
// $debug_tags[] = $tag;
// } );
//

function generateCallTrace() {
	$e     = new Exception();
	$trace = explode( "\n", $e->getTraceAsString() );
	// reverse array to make steps line up chronologically
	$trace = array_reverse( $trace );
	array_shift( $trace ); // remove {main}
	array_pop( $trace ); // remove call to this method
	$length = count( $trace );
	$result = array();

	for ( $i = 0; $i < $length; $i++ ) {
		$result[] = ( $i + 1 ) . ')' . substr( $trace[ $i ], strpos( $trace[ $i ], ' ' ) ); // replace '#someNum' with '$i)', set the right ordering
	}

	return "\t" . implode( "\n\t", $result );
}
