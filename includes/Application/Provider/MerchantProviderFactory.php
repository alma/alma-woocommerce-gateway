<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;

class MerchantProviderFactory {

	public function __invoke(): MerchantProvider {
		/** @var MerchantProvider $merchantProvider */
		$merchantProvider = Plugin::get_container()->get( MerchantProvider::class );

		return $merchantProvider;
	}
}
