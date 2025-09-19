<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Gateway\Infrastructure\Helper\UrlHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AlmaHelper {

	/**
	 * TODO Add constants for env
	 * Get Alma full URL depends on test or live mode (sandbox or not)
	 *
	 * @param string $env The environment.
	 * @param string $path as path to add after default scheme://host/ infos.
	 *
	 * @return string as full URL
	 */
	public static function getAlmaDashboardUrl( string $env = 'test', string $path = '' ): string {
		if ( 'live' === $env ) {
			return UrlHelper::checkAndCleanUrl( sprintf( 'https://dashboard.getalma.eu/%s', $path ) );
		}

		return UrlHelper::checkAndCleanUrl( sprintf( 'https://dashboard.sandbox.getalma.eu/%s', $path ) );
	}
}
