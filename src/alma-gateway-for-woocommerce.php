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

//namespace AlmaPayment;

//return;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

//if ( ! defined( 'ALMA_WC_VERSION' ) ) {
//	define( 'ALMA_WC_VERSION', '3.0.0' );
//}
//if ( ! defined( 'ALMA_WC_PLUGIN_FILE' ) ) {
//	define( 'ALMA_WC_PLUGIN_FILE', __FILE__ );
//}
//if ( ! defined( 'ALMA_WC_PLUGIN_BASENAME' ) ) {
//	define( 'ALMA_WC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
//}
//if ( ! defined( 'ALMA_WC_PLUGIN_URL' ) ) {
//	define( 'ALMA_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
//}

//return;

if ( ! defined( ALMA_WC_OLD_PLUGIN_FILE ) ) {
	define( ALMA_WC_OLD_PLUGIN_FILE, 'akismet/akismet.php' );
//	define( ALMA_WC_OLD_PLUGIN_FILE, 'alma-woocommerce-gateway/alma-woocommerce-gateway.php' );
}

if ( ! defined( ALMA_WC_OLD_PLUGIN_FILE ) ) {
	define( 'ALMA_WC_OLD_PLUGIN_FILE', 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php' );
}

if ( ! defined( ALMA_PREFIX_FOR_TMP_OPTIONS ) ) {
	define( 'ALMA_PREFIX_FOR_TMP_OPTIONS', 'my_tmp_' );
}

/**
 * @return void
 */
function deactivate_plugin_conditional() {

	//	error_log( 'function deactivate_plugin_conditional' );

	if ( is_plugin_active( ALMA_WC_OLD_PLUGIN_FILE) ) {
		deactivate_plugins( ALMA_WC_OLD_PLUGIN_FILE );
		error_log( 'deactivate_plugins - ' . ALMA_WC_OLD_PLUGIN_FILE );

		$plugin_file_path = WP_PLUGIN_DIR . '/' . ALMA_WC_OLD_PLUGIN_FILE;
		error_log( '$plugin_file_path - ' . $plugin_file_path );
		if ( file_exists( $plugin_file_path ) ) {
			delete_plugins( [ WP_PLUGIN_DIR . '/' . ALMA_WC_OLD_PLUGIN_FILE ] );
		}
	}
	return;
}
add_action( 'admin_init', 'deactivate_plugin_conditional', 0 );

/**
 * @param $plugin
 * @return void
 */
function delete_plugin_alma_callback( $plugin_file ) {

	error_log( 'delete_plugin_alma_callback ----------------------- ' . $plugin_file);

	if ( ALMA_WC_OLD_PLUGIN_FILE === $plugin_file ) {
		backup_alma_settings();
	}
}
add_action( 'delete_plugin', 'delete_plugin_alma_callback', 10, 1 );


/**
 * Backups the plugin options.
 *
 * @return void
 */
function backup_alma_settings() {
	$tmp_options = [
		'woocommerce_alma_settings' => get_option( 'woocommerce_alma_settings' ),
	];
	foreach ( $tmp_options  as $option_name => $option_value ) {
		update_option(  ALMA_PREFIX_FOR_TMP_OPTIONS . $option_name, $option_value);
	}
}

/**
 * @return void
 */
function my_plugin_activate() {
	$tmp_options = [
		'woocommerce_alma_settings' => get_option( 'woocommerce_alma_settings' ),
	];
	foreach ( $tmp_options  as $option_name => $option_value ) {
		$option_name = str_replace( ALMA_PREFIX_FOR_TMP_OPTIONS, '', $option_name );
		update_option( $option_name, $option_value);
	}
}
register_activation_hook( __FILE__, 'my_plugin_activate' );


//return;

/**
 * Return instance of Alma_Plugin.
 *
 * @return Alma_WC_Plugin
 * @noinspection PhpIncludeInspection
 */
function almapay_wc_plugin() {
	static $plugin;

//	$wp_get_active_and_valid_plugins = wp_get_active_and_valid_plugins();
//	pre( $wp_get_active_and_valid_plugins );
//
//	foreach ( $wp_get_active_and_valid_plugins  as $plugin_file ) {
//		if ( strpos( $plugin_file, 'alma-woocommerce-gateway.php' ) !== false ) {
//			// L'ancien plugin Alma est installé.
//			deactivate_plugins ( 'alma-woocommerce-gateway.php' );
//		}
//	}


	exit;

	if ( ! isset( $plugin ) ) {
		require_once ALMA_WC_PLUGIN_PATH . 'includes/alma-wc-functions.php';
		require_once ALMA_WC_PLUGIN_PATH . 'vendor/autoload.php';
		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-autoloader.php';

//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-admin-form.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-admin-internationalization-front-helper.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-checkout-helper.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-generic-handler.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-cart-handler.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-internationalization.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-logger.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-payment-gateway.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-payment-upon-trigger.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-payment-validation-error.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-payment-validator.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-plugin.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-product-handler.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-settings-helper.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-settings.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-shortcodes.php';
//		require_once ALMA_WC_PLUGIN_PATH . 'includes/class-alma-wc-webhooks.php';

		Alma_WC_Autoloader::autoload();
		$plugin = new Alma_WC_Plugin();
	}

	return $plugin;
}

almapay_wc_plugin()->try_running();


//$debug_tags = array();
//add_action( 'all', function ( $tag ) {
//	global $debug_tags;
//	if ( in_array( $tag, $debug_tags ) ) {
//		return;
//	}
//	error_log( '------> action = ' . $tag );
////	echo "<pre>" . $tag . "</pre>";
//	$debug_tags[] = $tag;
//} );

function pre( $p ) {
	echo '<pre>';
	print_r( $p );
	echo '</pre>';
}






