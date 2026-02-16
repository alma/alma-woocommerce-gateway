<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class ParameterHelper {

	/**
	 * Check and clean a parameter
	 *
	 * @param string $param The parameter to check and clean
	 *
	 * @return string|null
	 */
	public static function checkAndCleanParam( string $param ): ?string {
		return sanitize_text_field( $param ) ?? null;
	}
}
