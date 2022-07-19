<?php
/**
 * Alma WooCommerce payment gateway
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Class Alma_WC_Admin_Internationalization_Front_Helper.
 */
class Alma_WC_Admin_Internationalization_Front_Helper {

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

		return Alma_WC_Internationalization::map_locale( $current_admin_page_language );
	}
}
