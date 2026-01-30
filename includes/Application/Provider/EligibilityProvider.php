<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Client\Application\DTO\EligibilityDto;
use Alma\Client\Application\Endpoint\EligibilityEndpoint;
use Alma\Client\Application\Exception\Endpoint\EligibilityEndpointException;
use Alma\Client\Domain\Entity\EligibilityList;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Plugin\Application\Port\EligibilityProviderInterface;

class EligibilityProvider implements EligibilityProviderInterface, ProviderInterface {

	private EligibilityEndpoint $eligibilityEndpoint;

	/** @var EligibilityList */
	private EligibilityList $eligibilityList;

	public function __construct( EligibilityEndpoint $eligibilityEndpoint ) {

		$this->eligibilityEndpoint = $eligibilityEndpoint;
	}

	/**
	 * Get the eligibility list.
	 *
	 * @param EligibilityDto $eligibilityDto
	 *
	 * @return EligibilityList
	 * @throws EligibilityServiceException
	 */
	public function getEligibilityList( EligibilityDto $eligibilityDto ): EligibilityList {
		if ( ! isset( $this->eligibilityList ) ) {
			$this->retrieveEligibility( $eligibilityDto );
		}

		return $this->eligibilityList;
	}

	/**
	 * Retrieve the eligibility list based on the current cart total.
	 *
	 * @throws EligibilityServiceException
	 */
	public function retrieveEligibility( EligibilityDto $eligibilityDto ): void {

		try {
			$this->eligibilityList = $this->eligibilityEndpoint->getEligibilityList( $eligibilityDto );

		} catch ( EligibilityEndpointException $e ) {
			throw new EligibilityServiceException( 'Error retrieving eligibility: ' . $e->getMessage(), 0, $e );
		}
	}
}
