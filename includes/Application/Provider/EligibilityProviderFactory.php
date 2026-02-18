<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;

class EligibilityProviderFactory {

	public function __invoke(): EligibilityProvider {
		/** @var EligibilityProvider $eligibilityProvider */
		$eligibilityProvider = Plugin::get_container()->get( EligibilityProvider::class );

		return $eligibilityProvider;
	}
}
