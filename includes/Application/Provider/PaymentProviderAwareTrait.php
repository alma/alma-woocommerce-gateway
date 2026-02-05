<?php

namespace Alma\Gateway\Application\Provider;

trait PaymentProviderAwareTrait {

	/**
	 * The Payment Provider factory to lazy load the Payment Provider.
	 * @var PaymentProviderFactory
	 */
	private PaymentProviderFactory $paymentProviderFactory;

	/** @var PaymentProvider|null The Payment Provider */
	protected ?PaymentProvider $paymentProvider = null;

	/**
	 * Load the Payment Provider only when needed
	 * @return PaymentProvider
	 */
	private function getPaymentProvider(): PaymentProvider {
		if ( $this->paymentProvider === null ) {

			$this->paymentProvider = call_user_func( $this->paymentProviderFactory );
		}

		return $this->paymentProvider;
	}
}
