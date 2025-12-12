<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Gateway\Plugin;

class FeePlanProviderFactory {

	public function __invoke(): FeePlanProvider {
		/** @var FeePlanProvider $feePlanProvider */
		$feePlanProvider = Plugin::get_container()->get( FeePlanProvider::class );

		return $feePlanProvider;
	}
}
