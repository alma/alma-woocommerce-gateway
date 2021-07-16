<?php
/**
 * Alma payments plugin for WooCommerce
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Migration_Interface
 */
interface Alma_WC_Migrations_Interface {
	/**
	 * Constructor.
	 *
	 * @param string $from_version Version source from where to migrate.
	 * @param string $to_version Version target where to migrate.
	 */
	public function __construct( $from_version, $to_version );

	/**
	 * Migrate plugin data to a specific version
	 */
	public function up();
}
