<?php
/**
 * Alma_WC_Helper_Assets.
 *
 * @since 4.0.0
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_WC_Helper_Assets
 */
class Alma_WC_Helper_Assets {

	/**
	 *  Enqueue scripts needed into admin form.
	 *
	 * @return void
	 */
	public function alma_admin_enqueue_scripts() {
		wp_enqueue_style( 'alma-admin-styles', ALMA_WC_PLUGIN_URL . 'admin/css/alma-admin.css', array(), ALMA_WC_VERSION );

		wp_enqueue_script(
			'alma-admin-scripts',
			ALMA_WC_PLUGIN_URL . 'admin/js/alma-admin.js',
			array(
				'jquery',
				'jquery-effects-highlight',
				'jquery-ui-selectmenu',
			),
			ALMA_WC_VERSION,
			true
		);
	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_url( $path ) {
		return ALMA_WC_PLUGIN_URL . 'assets/' . $path;
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
		return ALMA_WC_PLUGIN_URL . 'admin/' . $path;
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

}
