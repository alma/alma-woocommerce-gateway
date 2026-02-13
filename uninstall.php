<?php
/**
 * Uninstall WooCommerce Gateway Alma plugin
 *
 * @package Alma_Gateway_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
global $wpdb;

delete_option( 'woocommerce_alma_config_gateway_settings' );
delete_option( 'alma_version' );
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
		$wpdb->esc_like( 'alma_migration_' ) . '%'
	)
);
