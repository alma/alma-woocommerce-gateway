<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Gateway\Plugin;

class PaymentProviderFactory {

	public function __invoke(): PaymentProvider {
		/** @var PaymentProvider $paymentProvider */
		$paymentProvider = Plugin::get_container()->get( PaymentProvider::class );

		return $paymentProvider;
	}
}
