<?php

namespace Alma\Gateway\Infrastructure\Helper;

class RenderHelper {

	/**
	 * Locate a template.
	 *
	 * @param string $templatePath The template path to locate.
	 * @param string $templateName The template name.
	 *
	 * @return string The located template path.
	 */
	public static function locate( string $templatePath, string $templateName ): string {
		return apply_filters( 'alma_gateway_template', $templatePath, $templateName );
	}
}
