<?php

namespace Alma\Gateway\Application\Provider;

trait FeePlanProviderAwareTrait {

	/**
	 * The Fee Plan Provider factory to lazy load the Fee Plan Provider.
	 * @var FeePlanProviderFactory
	 */
	private FeePlanProviderFactory $feePlanProviderFactory;

	/** @var FeePlanProvider|null The Fee Plan Provider */
	private ?FeePlanProvider $feePlanProvider = null;

	/**
	 * Load the Fee Plan Provider only when needed
	 * @return FeePlanProvider
	 */
	private function getFeePlanProvider(): FeePlanProvider {
		if ( $this->feePlanProvider === null ) {
			$this->feePlanProvider = call_user_func( $this->feePlanProviderFactory );
		}

		return $this->feePlanProvider;
	}
}
