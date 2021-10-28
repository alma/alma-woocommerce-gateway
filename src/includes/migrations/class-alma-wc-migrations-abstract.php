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
 * Class Alma_WC_Migrations_Abstract
 */
abstract class Alma_WC_Migrations_Abstract implements Alma_WC_Migrations_Interface {
	/**
	 * The target version where to migrate.
	 *
	 * @var string
	 */
	protected $to_version;
	/**
	 * The source version from where to migrate.
	 *
	 * @var string
	 */
	protected $from_version;

	/**
	 * Alma_WC_Migration_2_0_0 constructor.
	 *
	 * @param string $from_version The source version from where to migrate.
	 * @param string $to_version   The target version where to migrate.
	 */
	public function __construct( $from_version, $to_version ) {
		$this->from_version = $from_version;
		$this->to_version   = $to_version;
	}
}
