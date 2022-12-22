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

delete_option( 'woocommerce_alma_settings' );
delete_option( 'alma_version' );
delete_option( 'alma_warnings_handled' );
delete_option( 'alma_bootstrap_warning_message_dismissed' );
delete_option( 'alma_bootstrap_warning_message' );
$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( 'alma_migration_' ) . '%' ) );

