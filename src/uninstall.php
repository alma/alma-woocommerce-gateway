<?php
/**
 * Uninstall WooCommerce Gateway Alma plugin
 *
 * @package Alma_Gateway_For_Woocommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;

delete_option( 'wc_alma_settings' );
delete_option( 'alma_version' );
$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( 'alma_migration_' ) . '%' ) );

