<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Gateway\Application\Exception\Helper\TemplateHelperException;
use Alma\Gateway\Infrastructure\Helper\RenderHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
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
	 * 1. /themes/theme/woocommerce-plugin-templates/$templateName
	 * 2. /themes/theme/$templateName
	 * 3. /plugins/woocommerce-plugin-templates/templates/$templateName.
	 *
	 * @param string $templateName Template to load.
	 * @param string $subpath Subdirectories.
	 */
	public function locateTemplate( string $templateName, string $subpath = '' ): string {

		if ( $subpath ) {
			$templatePath = PluginHelper::getPluginPath() . 'public/templates/' . $subpath . '/' . $templateName;
		} else {
			$templatePath = PluginHelper::getPluginPath() . 'public/templates/' . $templateName;
		}

		return RenderHelper::locate( $templatePath, $templateName );
	}

	/**
	 * Get template.
	 * @TODO need refactor for test file_exists need a test
	 *
	 * @param string $template_name Template to load.
	 * @param array  $args Args passed for the template file.
	 * @param string $subpath Path to template files.
	 *
	 * @throws TemplateHelperException
	 * @see locate_template()
	 *
	 * @sonar It's mandatory to use include_once method here.
	 * @phpcs We use extract to pass variables to the template.
	 */
	public function getTemplate( string $template_name, array $args = array(), string $subpath = '' ) {

		if ( is_array( $args ) ) {
			// We master our data. It's not get or post.
			extract( $args );// phpcs:ignore
		}

		$template_file = $this->locateTemplate( $template_name, $subpath );

		if ( ! file_exists( $template_file ) ) {
			throw new TemplateHelperException(
				sprintf( 'Template file %s does not exist.', esc_html( $template_file ) )
			);
		}

		include_once $template_file;// NOSONAR -- It's mandatory to use include_once method here.
	}
}
