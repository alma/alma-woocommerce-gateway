<?php
/**
 * GeneralHelper.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Admin/Helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Helpers\InternationalizationHelper;

/**
 * Alma_Admin_Helper_General
 *
 * Helper for the plugin admin
 */
class GeneralHelper {

	/**
	 * Internalionalization Helper.
	 *
	 * @var InternationalizationHelper
	 */
	protected $internalization_helper;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->internalization_helper = new InternationalizationHelper();
	}
	/**
	 * Returns if the language code in parameter matches the current admin page language.
	 *
	 * @param string $code_lang A language code.
	 *
	 * @return bool
	 */
	public function is_lang_selected( $code_lang ) {
		if ( $this->get_current_admin_page_language() === $code_lang ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the current admin page locale, formatted as xx_XX.
	 *
	 * @return string
	 */
	public function get_current_admin_page_language() {
		$current_admin_page_language = get_locale();

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$current_admin_page_language = \ICL_LANGUAGE_CODE;
		}

		return $this->internalization_helper->map_locale( $current_admin_page_language );
	}
}


