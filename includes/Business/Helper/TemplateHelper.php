<?php

namespace Alma\Gateway\Business\Helper;

use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TemplateLoaderHelper
 */
class TemplateHelper {

	/**
	 * Locate template.
	 *
	 * Locate the called template.
	 * Search Order:
	 * 1. /themes/theme/woocommerce-plugin-templates/$template_name
	 * 2. /themes/theme/$template_name
	 * 3. /plugins/woocommerce-plugin-templates/templates/$template_name.
	 *
	 * @param string $template_name Template to load.
	 * @param string $subpath Subdirectories.
	 */
	public function locate_template( string $template_name, string $subpath = '' ) {

		$template = Plugin::get_instance()->get_plugin_path() . 'public/templates/' . $template_name;

		if ( ! empty( $subpath ) ) {
			$template = Plugin::get_instance()->get_plugin_path() . 'public/templates/' . $subpath . '/' . $template_name;
		}

		return apply_filters( 'alma_locate_template', $template, $template_name );
	}

	/**
	 * Get template.
	 *
	 * @param string $template_name Template to load.
	 * @param array  $args Args passed for the template file.
	 * @param string $subpath Path to template files.
	 *
	 * @see locate_template()
	 *
	 * @sonar It's mandatory to use include_once method here.
	 * @phpcs We use extract to pass variables to the template.
	 */
	public function get_template( string $template_name, array $args = array(), string $subpath = '' ) {

		if ( is_array( $args ) ) {
			// We master our data. It's not get or post.
			extract( $args );// phpcs:ignore
		}

		$template_file = $this->locate_template( $template_name, $subpath );

		if ( ! file_exists( $template_file ) ) {
			// @todo use a proxy
			_doing_it_wrong(
				__FUNCTION__,
				sprintf( '<code>%s</code> does not exist.', esc_html( $template_file ) ),
				'4.2.0'
			);

			return;
		}

		include_once $template_file;// NOSONAR -- It's mandatory to use include_once method here.
	}
}
