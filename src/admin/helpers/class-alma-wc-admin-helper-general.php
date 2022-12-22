<?php
/**
 * Alma_WC_Admin_Helper_General.
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/admin/helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Admin_Helper_General
 *
 * Helper for the plugin admin
 */
class Alma_WC_Admin_Helper_General {

	/**
	 * Get the html for the image to display
	 *
	 * @param string $title the alt attribute.
	 * @param string $id Used for the filter.
	 * @param string $path By default, the alma logo.
	 *
	 * @return string
	 */
	public static function get_icon( $title, $id, $path = Alma_WC_Helper_Constants::PATH_ALMA_LOGO ) {
		$icon_url = Alma_WC_Helper_Assets::get_asset_url( $path );
		$icon     = '<img src="' . WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $title ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'alma_wc_gateway_icon', $icon, $id );
	}


	/**
	 * Returns if the language code in parameter matches the current admin page language.
	 *
	 * @param string $code_lang A language code.
	 *
	 * @return bool
	 */
	public static function is_lang_selected( $code_lang ) {
		if ( self::get_current_admin_page_language() === $code_lang ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the current admin page locale, formatted as xx_XX.
	 *
	 * @return string
	 */
	public static function get_current_admin_page_language() {

		$current_admin_page_language = get_locale();

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$current_admin_page_language = ICL_LANGUAGE_CODE;
		}

		return Alma_WC_Helper_Internationalization::map_locale( $current_admin_page_language );
	}
}


