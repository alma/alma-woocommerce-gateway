<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

trait EligibilityProviderAwareTrait {

	/**
	 * The Eligibility Provider factory to lazy load the Eligibility Provider.
	 * @var EligibilityProviderFactory
	 */
	private EligibilityProviderFactory $eligibilityProviderFactory;

	/** @var EligibilityProvider|null The Eligibility Provider */
	private ?EligibilityProvider $eligibilityProvider = null;

	/**
	 * Load the Eligibility Provider only when needed
	 * @return EligibilityProvider
	 */
	private function getEligibilityProvider(): EligibilityProvider {
		if ( $this->eligibilityProvider === null ) {
			$this->eligibilityProvider = call_user_func( $this->eligibilityProviderFactory );
		}

		return $this->eligibilityProvider;
	}
}
