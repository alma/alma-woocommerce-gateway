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
class Alma_WC_Migration_2_0_0 extends Alma_WC_Migrations_Abstract {

	/**
	 * Options to update
	 *
	 * @var array
	 */
	private $option_keys = array(
		'min_amount_2x'  => 'min_amount_general_2_0_0',
		'min_amount_3x'  => 'min_amount_general_3_0_0',
		'min_amount_4x'  => 'min_amount_general_4_0_0',
		'min_amount_10x' => 'min_amount_general_10_0_0',
		'min_amount_12x' => 'min_amount_general_12_0_0',
		'max_amount_2x'  => 'max_amount_general_2_0_0',
		'max_amount_3x'  => 'max_amount_general_3_0_0',
		'max_amount_4x'  => 'max_amount_general_4_0_0',
		'max_amount_10x' => 'max_amount_general_10_0_0',
		'max_amount_12x' => 'max_amount_general_12_0_0',
		'enabled_2x'     => 'enabled_general_2_0_0',
		'enabled_3x'     => 'enabled_general_3_0_0',
		'enabled_4x'     => 'enabled_general_4_0_0',
		'enabled_10x'    => 'enabled_general_10_0_0',
		'enabled_12x'    => 'enabled_general_12_0_0',
	);

	/**
	 * Migrate settings
	 */
	public function up() {
		if ( version_compare( $this->from_version, '2.0', '<' ) ) {
			$options = get_option( Alma_WC_Settings::OPTIONS_KEY, array() );
			delete_option( Alma_WC_Settings::OPTIONS_KEY );
			foreach ( $this->option_keys as $key => $new_key ) {
				if ( ! isset( $options[ $key ] ) ) {
					continue;
				}
				$value = $options[ $key ];
				unset( $options[ $key ] );
				$options[ $new_key ] = $value;
			}
			update_option( Alma_WC_Settings::OPTIONS_KEY, $options );
		}
	}
}
