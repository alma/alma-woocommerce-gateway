<?php
/**
 * InternationalizationHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * InternationalizationHelper
 */
class InternationalizationHelper {


	/**
	 * Available languages mo files hash.
	 *
	 * @var array
	 */
	private static $languages = array();

	/**
	 * Load languages list.
	 *
	 * @return array
	 */
	public static function get_list_languages() {
		if ( self::is_wpml_active() ) {
			return self::get_wpml_list_languages();
		}

		return array();
	}

	/**
	 * Checks if WPML is active.
	 *
	 * @return bool
	 */
	public static function is_wpml_active() {
		return (bool) did_action( 'wpml_loaded' );
	}

	/**
	 * Load WPML languages list.
	 *
	 * @return array
	 */
	public static function get_wpml_list_languages() {
		$languages_list = array();
		foreach ( icl_get_languages() as $infos_lang ) {
			$languages_list[ self::map_locale( $infos_lang['default_locale'] ) ] = $infos_lang['translated_name'];
		}
		return $languages_list;
	}

	/**
	 * Returns locale in format xx_XX for WP locale format compliance.
	 * The value returned by this function is the key language code for the <select> of languages.
	 * This value MUST be the same as the one returned by the get_locale() function.
	 *
	 * @param string $locale The locale.
	 *
	 * @return string $locale The locale formatted on WordPress way.
	 */
	public static function map_locale( $locale ) {
		$locale = str_replace( '-', '_', $locale );
		if ( 2 === strlen( $locale ) ) {
			$locale .= '_' . strtoupper( $locale );
		}
		return $locale;
	}

	/**
	 * Checks if this website is multilingual.
	 *
	 * @return bool
	 */
	public static function is_site_multilingual() {
		return self::is_wpml_active();
	}

	/**
	 * Get string translated in another language.
	 *
	 * @param string $string String to translate, in default language (english).
	 * @param string $code_lang The desired language code for the translated string.
	 *
	 * @return string
	 */
	public static function get_translated_text( $string, $code_lang ) {
		$translation = $string;

		$mo_path_file = ALMA_PLUGIN_PATH . 'languages/alma-gateway-for-woocommerce-' . $code_lang . '.mo';
		if ( ! file_exists( $mo_path_file ) ) {
			return $translation;
		}

		if ( ! isset( self::$languages[ $code_lang ] ) ) {
			$mo = new \MO();
			$mo->import_from_file( $mo_path_file );
			self::$languages[ $code_lang ] = $mo;
		}
		$mo = self::$languages[ $code_lang ];

		if ( self::entry_exists( $mo, $string ) ) {
			$translation = $mo->entries[ $string ]->translations[0];
		}
		return $translation;
	}

	/**
	 * Tells is an entry string exists in a .mo file.
	 *
	 * @param \MO    $mo The MO object.
	 * @param String $entry The entry string to find.
	 *
	 * @return bool
	 */
	protected static function entry_exists( $mo, $entry ) {
		return isset( $mo->entries )
			&& isset( $mo->entries[ $entry ] )
			&& isset( $mo->entries[ $entry ]->translations )
			&& isset( $mo->entries[ $entry ]->translations[0] );
	}

	/**
	 * Adds all the translated fields for one field.
	 *
	 * @param string $field_name The field name.
	 * @param array  $field_infos The information for this field.
	 * @param string $default The default value for the field.
	 *
	 * @return array
	 */
	public static function generate_i18n_field( $field_name, $field_infos, $default ) {
		$lang_list = self::get_list_languages();

		if (
			self::is_site_multilingual()
			&& count( $lang_list ) > 0
		) {
			$new_fields = array();

			foreach ( $lang_list as $code_lang => $label_lang ) {
				$new_file_key                 = $field_name . '_' . $code_lang;
				$new_field_infos              = $field_infos;
				$new_field_infos['type']      = 'text_alma_i18n';
				$new_field_infos['class']     = $code_lang;
				$new_field_infos['default']   = self::get_translated_text( $default, $code_lang );
				$new_field_infos['lang_list'] = $lang_list;

				$new_fields[ $new_file_key ] = $new_field_infos;
			}

			return $new_fields;
		}

		$additional_infos = array(
			'type'    => 'text',
			'class'   => 'alma-i18n',
			'default' => $default,
		);

		return array( $field_name => array_merge( $field_infos, $additional_infos ) );
	}

}
