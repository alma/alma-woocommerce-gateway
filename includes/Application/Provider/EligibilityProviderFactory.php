<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Gateway\Plugin;

class EligibilityProviderFactory {

	public function __invoke() {
		/** @var EligibilityProvider $eligibilityProvider */
		$eligibilityProvider = Plugin::get_container()->get( EligibilityProvider::class );

		return $eligibilityProvider;
	}
}
