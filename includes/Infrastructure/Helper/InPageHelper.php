<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class InPageHelper {

	/**
	 * Display Alma In-Page script on the page.
	 *
	 * @param string $merchantId
	 * @param string $environment
	 *
	 * @return void
	 */
	public function displayInPage( string $merchantId, string $environment ): void {
		AssetsHelper::enqueueInPageScript( '1.0.0', $merchantId, $environment );
	}
}
