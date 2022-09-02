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

require_once dirname( __FILE__ ) . '/includes/class-alma-wc-settings.php';
delete_option( Alma_WC_Settings::OPTIONS_KEY );
delete_option( 'alma_version' );
delete_option( 'alma_warnings_handled' );
delete_option( 'alma_bootstrap_warning_message_dismissed' );
delete_option( 'alma_bootstrap_warning_message' );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'alma_migration_' ) . '%' ) );

/** LEGAL CHECKOUT FEATURE
 * $alma_wc_timestamp = wp_next_scheduled( Alma_WC_Share_Of_Checkout::CRON_ACTION );
* wp_unschedule_event( $alma_wc_timestamp, Alma_WC_Share_Of_Checkout::CRON_ACTION );
 */
