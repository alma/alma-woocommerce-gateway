<?php
/**
 * TemplateLoaderHelper.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TemplateLoaderHelper
 */
class TemplateLoaderHelper {

	/**
	 * Locate template.
	 *
	 * Locate the called template.
	 * Search Order:
	 * 1. /themes/theme/woocommerce-plugin-templates/$template_name
	 * 2. /themes/theme/$template_name
	 * 3. /plugins/woocommerce-plugin-templates/templates/$template_name.
	 *
	 * @param   string $template_name          Template to load.
	 * @param   string $subpath          Subdirectories.
	 */
	public function locate_template( $template_name, $subpath = '' ) {

		$template = ALMA_PLUGIN_PATH . 'public/templates/' . $template_name;

		if ( ! empty( $subpath ) ) {
			$template = ALMA_PLUGIN_PATH . 'public/templates/' . $subpath . '/' . $template_name;
		}

		return apply_filters( 'alma_locate_template', $template, $template_name );
	}

	/**
	 * Get template.
	 *
	 * @see locate_template()
	 *
	 * @param string $template_name          Template to load.
	 * @param array  $args                   Args passed for the template file.
	 * @param string $subpath           Path to template files.
	 */
	public function get_template( $template_name, $args = array(), $subpath = '' ) {

		if ( is_array( $args ) ) {
			// We master our data. It's not get or post.
			extract( $args ); // phpcs:ignore
		}

		$template_file = $this->locate_template( $template_name, $subpath );

		if ( ! file_exists( $template_file ) ) {
			_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', esc_html( $template_file ) ), '4.2.0' );
			return;
		}

		include $template_file;

	}
}

