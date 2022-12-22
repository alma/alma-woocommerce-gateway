<?php
/**
 * Alma_Assets.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma_WC\Helpers
 */

namespace Alma_WC\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Assets
 */
class Alma_Assets {


	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_url( $path ) {
		return ALMA_PLUGIN_URL . 'assets/' . $path;
	}

	/**
	 * Get admin logs url.
	 *
	 * @return string
	 */
	public static function get_admin_logs_url() {
		return admin_url( 'admin.php?page=wc-status&tab=logs' );
	}

	/**
	 * Get the admin asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_admin_url( $path ) {
		return ALMA_PLUGIN_URL . 'assets/admin/' . $path;
	}

	/**
	 * Link to settings screen.
	 *
	 * @param bool $alma_section Go to alma section.
	 *
	 * @return string
	 */
	public static function get_admin_setting_url( $alma_section = true ) {
		if ( $alma_section ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma' );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout' );
	}

	/**
	 * Get Alma full URL depends on test or live mode (sandbox or not)
	 *
	 * @param string $env The environment.
	 * @param string $path as path to add after default scheme://host/ infos.
	 *
	 * @return string as full URL
	 */
	public static function get_alma_dashboard_url( $env = 'test', $path = '' ) {
		if ( 'live' === $env ) {
			/* translators: %s -> path to add after dashboard url */
			return esc_url( sprintf( __( 'https://dashboard.getalma.eu/%s', 'alma-gateway-for-woocommerce' ), $path ) );
		}

		/* translators: %s -> path to add after sandbox dashboard url */

		return esc_url( sprintf( __( 'https://dashboard.sandbox.getalma.eu/%s', 'alma-gateway-for-woocommerce' ), $path ) );
	}

	/**
	 *  Enqueue scripts needed into admin form.
	 *
	 * @return void
	 */
	public function alma_admin_enqueue_scripts() {
		wp_enqueue_style( 'alma-admin-styles', ALMA_PLUGIN_URL . 'assets/admin/css/alma-admin.css', array(), ALMA_VERSION );

		wp_enqueue_script(
			'alma-admin-scripts',
			ALMA_PLUGIN_URL . 'assets/admin/js/alma-admin.js',
			array(
				'jquery',
				'jquery-effects-highlight',
				'jquery-ui-selectmenu',
			),
			ALMA_VERSION,
			true
		);
	}

}
