<?php
/**
 * Uninstall WooCommerce Gateway Alma plugin
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;


error_log(' gil uninstall.php' );

require_once dirname( __FILE__ ) . '/includes/class-alma-wc-settings.php';
delete_option( Alma_WC_Settings::OPTIONS_KEY );
delete_option( 'alma_version' );
delete_option( 'alma_warnings_handled' );
delete_option( 'alma_bootstrap_warning_message_dismissed' );
delete_option( 'alma_bootstrap_warning_message' );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'alma_migration_' ) . '%' ) );

$timestamp = wp_next_scheduled( 'alma_cron_hook_test' );
wp_unschedule_event( $timestamp, 'alma_cron_hook_test' );


error_log(' gil uninstall.php 2' );

//register_deactivation_hook( __FILE__, 'bl_deactivate' );
//
//function bl_deactivate() {
//	$timestamp = wp_next_scheduled( 'bl_cron_hook' );
//	wp_unschedule_event( $timestamp, 'bl_cron_hook' );
//}




