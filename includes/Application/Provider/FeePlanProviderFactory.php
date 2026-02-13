<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;

class FeePlanProviderFactory {

	public function __invoke(): FeePlanProvider {
		/** @var FeePlanProvider $feePlanProvider */
		$feePlanProvider = Plugin::get_container()->get( FeePlanProvider::class );

		return $feePlanProvider;
	}
}
