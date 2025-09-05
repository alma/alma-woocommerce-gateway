<?php

namespace Alma\Gateway\Application\Service\API;

use Alma\API\Endpoint\EligibilityEndpoint;
use Alma\API\Entity\EligibilityList;
use Alma\API\Exception\Endpoint\EligibilityEndpointException;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Exception\EligibilityServiceException;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;

class EligibilityService {

	private EligibilityEndpoint $eligibilityEndpoint;

	/** @var EligibilityList */
	private EligibilityList $eligibilityList;

	public function __construct( EligibilityEndpoint $eligibilityEndpoint ) {

		$this->eligibilityEndpoint = $eligibilityEndpoint;
	}

	/**
	 * Retrieve the eligibility list based on the current cart total.
	 *
	 * @throws EligibilityServiceException|ParametersException
	 */
	public function retrieveEligibility() {
		try {
			$purchase_amount = WooCommerceProxy::get_cart_total();
			if ( $purchase_amount > 0 ) {
				$this->eligibilityList = $this->eligibilityEndpoint->getEligibilityList( array( 'purchase_amount' => $purchase_amount ) );
			} else {
				$this->eligibilityList = new EligibilityList();
			}
		} catch ( EligibilityEndpointException $e ) {
			throw new EligibilityServiceException( 'Error retrieving eligibility: ' . $e->getMessage() );
		}
	}

	/**
	 * Get the eligibility list.
	 *
	 * @throws EligibilityServiceException|ParametersException
	 */
	public function getEligibilityList(): EligibilityList {
		if ( ! isset( $this->eligibilityList ) ) {
			$this->retrieveEligibility();
		}

		return $this->eligibilityList;
	}
}
