<?php
/**
 * Alma_Assets_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Assets_Helper
 */
class Alma_Assets_Helper {


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
	 * Get asset public url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_public_url( $path ) {
		return ALMA_PLUGIN_URL . 'public/' . $path;
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
		wp_enqueue_style( 'alma-admin-styles', ALMA_PLUGIN_URL . 'assets/admin/css/alma.css', array(), ALMA_VERSION );

		wp_enqueue_script(
			'alma-admin-scripts',
			ALMA_PLUGIN_URL . 'assets/admin/js/alma.js',
			array(
				'jquery',
				'jquery-effects-highlight',
				'jquery-ui-selectmenu',
			),
			ALMA_VERSION,
			true
		);
	}

	/**
	 * Get the html for the image to display
	 *
	 * @param string $title the alt attribute.
	 * @param string $id Used for the filter.
	 * @param string $path By default, the alma logo.
	 *
	 * @return string
	 */
	public static function get_icon( $title, $id, $path = Alma_Constants_Helper::ALMA_LOGO_PATH ) {
		$icon_url = static::get_public_url( $path );
		$icon     = '<img src="' . \WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $title ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'alma_wc_gateway_icon', $icon, $id );
	}
}
