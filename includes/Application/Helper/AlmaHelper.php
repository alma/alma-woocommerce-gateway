<?php

namespace Alma\Gateway\Application\Helper;

use Alma\Client\Domain\ValueObject\Environment;
use Alma\Gateway\Infrastructure\Helper\UrlHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class AlmaHelper {

	/**
	 * Get Alma full URL depends on test or live mode (sandbox or not)
	 *
	 * @param Environment $environment The environment.
	 * @param string      $path as path to add after default scheme://host/ infos.
	 *
	 * @return string as full URL
	 */
	public static function getAlmaDashboardUrl( Environment $environment, string $path = '' ): string {
		if ( Environment::LIVE_MODE === $environment->getMode() ) {
			return UrlHelper::checkAndCleanUrl( sprintf( 'https://dashboard.getalma.eu/%s', $path ) );
		}

		return UrlHelper::checkAndCleanUrl( sprintf( 'https://dashboard.sandbox.getalma.eu/%s', $path ) );
	}
}
