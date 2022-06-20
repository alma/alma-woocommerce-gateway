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

if ( ! defined( 'ALMA_WC_VERSION' ) ) {
	define( 'ALMA_WC_VERSION', '3.0.0' );
}
if ( ! defined( 'ALMA_WC_PLUGIN_FILE' ) ) {
	define( 'ALMA_WC_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ALMA_WC_PLUGIN_PATH' ) ) {
	define( 'ALMA_WC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ALMA_WC_PLUGIN_URL' ) ) {
	define( 'ALMA_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'ALMA_WC_OLD_PLUGIN_FILE' ) ) {
	// @todo en vrai il faudra virer "-2.6.1"
	define( 'ALMA_WC_OLD_PLUGIN_FILE', 'alma-woocommerce-gateway-2.6.1/alma-woocommerce-gateway.php' );
}

if ( ! defined( 'ALMA_WC_NEW_PLUGIN_FILE' ) ) {
	// @todo en vrai ça sera "alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php"
	define( 'ALMA_WC_NEW_PLUGIN_FILE', 'alma-woocommerce-gateway/alma-gateway-for-woocommerce.php' );
}

if ( ! defined( 'ALMA_PREFIX_FOR_TMP_OPTIONS' ) ) {
	define( 'ALMA_PREFIX_FOR_TMP_OPTIONS', 'alma_tmp4_' );
}

if ( ! defined( 'ALMA_PLUGIN_ACTIVATION_OPTION_NAME' ) ) {
	define( 'ALMA_PLUGIN_ACTIVATION_OPTION_NAME', 'alma_plugin_activation_option_name' );
}

if ( ! defined( 'ALMA_PLUGIN_ACTIVATION_FLAG' ) ) {
	define( 'ALMA_PLUGIN_ACTIVATION_FLAG', 'alma_new_installed' );
}

/**
 * Alma plugin activation hook.
 *
 * @return void
 */
function new_alma_plugin_activation_hook() {
	error_log( 'new_alma_plugin_activation_hook()' );
	backup_alma_settings();
	deactivate_old_alma_plugin();
	add_option( ALMA_PLUGIN_ACTIVATION_OPTION_NAME, '1' );
}
register_activation_hook( __FILE__, 'new_alma_plugin_activation_hook' );

/**
 * Alma admin init action hook callback.
 *
 * @return void
 */
function alma_wc_admin_init() {
	error_log( 'alma_wc_admin_init' );

//	Pour virer toutes les options Alma.
//	global $wpdb;
//	$get_query = $wpdb->query( sprintf( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%s'", 'alma' . '%' ) );
//	error_log( 'delete from wp_options = ' . $get_query );
//	exit;

	if ( ! is_admin() ) {
		return;
	}

	if ( '1' === get_option( ALMA_PLUGIN_ACTIVATION_OPTION_NAME ) ) {
		delete_option( ALMA_PLUGIN_ACTIVATION_OPTION_NAME );
		wp_safe_redirect ( add_query_arg( [ ALMA_PLUGIN_ACTIVATION_FLAG => 1 ], admin_url( '/plugins.php' ) ) );
		exit;
	}

	// Delete old version of Alma plugin.
	if (  isset( $_GET[ ALMA_PLUGIN_ACTIVATION_FLAG ] )  && '1' === $_GET[ ALMA_PLUGIN_ACTIVATION_FLAG ] ) {
		delete_old_alma_plugin();
		import_alma_settings();
	}
}
add_action( 'admin_init', 'alma_wc_admin_init' );

function general_admin_notice(){
	global $pagenow;
	if ( $pagenow == 'plugins.php' ) {
		echo '<div class="notice updated is-dismissible"><p>' .
			__( 'The new version of Alma plugin has successfully be installed and the old version has been removed. Thank you for this update!', 'alma-gateway-for-woocommerce' ) .
			'</p></div>';
	}
}
add_action('admin_notices', 'general_admin_notice');

$GLOBALS['tmp_options'] = [
	'woocommerce_alma_settings',
	'alma_warnings_handled'
];

/**
 * Backups the plugin settings, with different option names.
 *
 * @return void
 */
function backup_alma_settings() {

	error_log( '-----------------------------' );
	error_log( 'backup_alma_settings()' );
	error_log( '-----------------------------' );

	foreach ( $GLOBALS['tmp_options']  as $option_name ) {

		$tmp_option_name = ALMA_PREFIX_FOR_TMP_OPTIONS . $option_name;
		$option_value    = get_option( $option_name );

		delete_option( $tmp_option_name );
		update_option( $tmp_option_name, $option_value );
		error_log( 'update_option : $tmp_option_name = ' . $tmp_option_name . ' et $option_value = ' . serialize( $option_value ) );
	}
	error_log( '---------------------------------------------------' );
}

/**
 * Backups the plugin options.
 *
 * @return void
 */
function import_alma_settings() {

	error_log( '-----------------------------' );
	error_log( 'import_alma_settings()' );
	error_log( '-----------------------------' );

	foreach ( $GLOBALS['tmp_options']  as $option_name ) {

		$option_value = get_option( ALMA_PREFIX_FOR_TMP_OPTIONS . $option_name );

		delete_option( $option_name );
		update_option( $option_name, $option_value);
		error_log( 'update_option : $option_name = ' . $option_name . ' et $option_value = ' . serialize( $option_value ) );
	}
	error_log( '---------------------------------------------------' );
}

/**
 * Deactivate the old version of Alma plugin.
 *
 * @return void
 */
function deactivate_old_alma_plugin() {
	if ( is_plugin_active( ALMA_WC_OLD_PLUGIN_FILE) ) {
		error_log( 'ALMA_WC_OLD_PLUGIN_FILE désactivé !' );
		deactivate_plugins( ALMA_WC_OLD_PLUGIN_FILE );
	}
	else {
		error_log( 'ALMA_WC_OLD_PLUGIN_FILE NON désactivé !' );
	}
}

/**
 * Delete the old version of Alma plugin.
 *
 * @return void
 */
function delete_old_alma_plugin() {
	$delete_plugins = delete_plugins( [ ALMA_WC_OLD_PLUGIN_FILE ] );
}

/**
 * @param $plugin
 * @return void
 */
//function alma_activated_plugin_new( $plugin_file ) {
//
//	error_log( 'alma_activated_plugin_new() --------> ' . $plugin_file);
//
//	if ( ALMA_WC_NEW_PLUGIN_FILE === $plugin_file ) {
//		import_alma_settings();
//	}
//}
//add_action( 'activated_plugin', 'alma_activated_plugin_new', 10, 1 );

/**
 * Return instance of Alma_Plugin.
 *
 * @return Alma_WC_Plugin
 * @noinspection PhpIncludeInspection
 */
function almapay_wc_plugin() {
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
//

function generateCallTrace()
{
	$e = new Exception();
	$trace = explode("\n", $e->getTraceAsString());
	// reverse array to make steps line up chronologically
	$trace = array_reverse($trace);
	array_shift($trace); // remove {main}
	array_pop($trace); // remove call to this method
	$length = count($trace);
	$result = array();

	for ($i = 0; $i < $length; $i++)
	{
		$result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
	}

	return "\t" . implode("\n\t", $result);
}