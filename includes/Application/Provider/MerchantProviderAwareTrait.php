<?php

namespace Alma\Gateway\Application\Provider;

trait MerchantProviderAwareTrait {

	/**
	 * The Merchant Provider factory to lazy load the Merchant Provider.
	 * @var MerchantProviderFactory
	 */
	private MerchantProviderFactory $merchantProviderFactory;

	/** @var MerchantProvider|null The Merchant Provider */
	private ?MerchantProvider $merchantProvider = null;

	/**
	 * Load the Merchant Provider only when needed
	 * @return MerchantProvider
	 */
	private function getMerchantProvider(): MerchantProvider {
		if ( $this->merchantProvider === null ) {
			$this->merchantProvider = call_user_func( $this->merchantProviderFactory );
		}

		return $this->merchantProvider;
	}
}
