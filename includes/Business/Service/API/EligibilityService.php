<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\EligibilityEndpoint;
use Alma\API\Exceptions\EligibilityServiceException;

class EligibilityService {

	private EligibilityEndpoint $eligibility_endpoint;

	public function __construct( EligibilityEndpoint $eligibility_endpoint ) {

		$this->eligibility_endpoint = $eligibility_endpoint;
	}

	/**
	 * @throws EligibilityServiceException
	 */
	public function is_eligible( $eligibility_data ): bool {

		$eligibility = $this->eligibility_endpoint->eligibility( $eligibility_data );
		foreach ( $eligibility as $eligibility_item ) {
			if ( $eligibility_item->isEligible() ) {
				return true;
			}
		}

		return false;
	}
}
