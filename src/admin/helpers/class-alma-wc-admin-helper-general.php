<?php
/**
 * Alma order helper.
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Admin_Helper_General
 */
class Alma_WC_Admin_Helper_General {

	const PATH_ALMA_LOGO = 'images/alma_logo.svg';

	/**
	 * @param string $title
	 * @param string $id
	 * @param string $path By default, the alma logo
	 *
	 * @return string
	 */
	public static function get_icon( $title, $id, $path = self::PATH_ALMA_LOGO ) {
		$icon_url = alma_wc_plugin()->get_asset_url( $path );
		$icon     = '<img src="' . WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $title ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'alma_wc_gateway_icon', $icon, $id );
	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_admin_url( $path ) {
		return ALMA_WC_PLUGIN_URL . 'admin/' . $path;
	}

	/**
	 * Get the admin partial
	 *
	 * @param string $file_name
	 *
	 * @return string
	 */
	public static function get_admin_partial( $file_name ) {
		return ALMA_WC_PLUGIN_URL . 'admin/partials/' . $file_name;
	}


}


