<?php
/**
 * Alma payments plugin for WooCommerce
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}
if ( ! defined( 'ALMA_WC_PLUGIN_PATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Migration
 */
class Alma_WC_Migration_1_3_1 extends Alma_WC_Migrations_Abstract {

	/**
	 * Migrate settings
	 */
	public function up() {
		update_option( 'alma_migration_new_version', 'version 1.3.1 updated' );
	}
}
