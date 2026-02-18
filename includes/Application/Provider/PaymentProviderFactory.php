<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;

class PaymentProviderFactory {

	public function __invoke(): PaymentProvider {
		/** @var PaymentProvider $paymentProvider */
		$paymentProvider = Plugin::get_container()->get( PaymentProvider::class );

		return $paymentProvider;
	}
}
