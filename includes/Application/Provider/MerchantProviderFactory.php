<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Gateway\Plugin;

class MerchantProviderFactory {

	public function __invoke(): MerchantProvider {
		/** @var MerchantProvider $merchantProvider */
		$merchantProvider = Plugin::get_container()->get( MerchantProvider::class );

		return $merchantProvider;
	}
}
