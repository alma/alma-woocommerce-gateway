<?php
/**
 * Alma_Template_Loader.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Template_Loader
 */
class Alma_Template_Loader {
	/**
	 * Locate template.
	 *
	 * Locate the called template.
	 * Search Order:
	 * 1. /themes/theme/woocommerce-plugin-templates/$template_name
	 * 2. /themes/theme/$template_name
	 * 3. /plugins/woocommerce-plugin-templates/templates/$template_name.
	 *
	 * @since 1.0.0
	 *
	 * @param   string $template_name          Template to load.
	 */
	public function locate_template( $template_name, $subpath = '' ) {

		$template = ALMA_PLUGIN_PATH . 'public/templates/' . $template_name;

		if ( ! empty( $subpath ) ) {
			$template = ALMA_PLUGIN_PATH . 'public/templates/' . $subpath . '/' . $template_name;
		}

		return apply_filters( 'wcpt_locate_template', $template, $template_name );
	}

	/**
	 * Get template.
	 *
	 * Search for the template and include the file.
	 *
	 * @since 1.0.0
	 *
	 * @see wcpt_locate_template()
	 *
	 * @param string $template_name          Template to load.
	 * @param array  $args                   Args passed for the template file.
	 * @param string $string $template_path  Path to templates.
	 * @param string $default_path           Default path to template files.
	 */
	public function get_template( $template_name, $args = array(), $subpath = '' ) {

		if ( is_array( $args ) && isset( $args ) ) :
			extract( $args );
		endif;

		$template_file = $this->locate_template( $template_name, $subpath );

		if ( ! file_exists( $template_file ) ) :
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
			return;
		endif;

		include $template_file;

	}
}

