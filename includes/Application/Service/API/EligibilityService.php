<?php

namespace Alma\Gateway\Application\Service\API;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Exception\EligibilityServiceException;
use Alma\API\Domain\Service\API\EligibilityServiceInterface;
use Alma\API\Endpoint\EligibilityEndpoint;
use Alma\API\Entity\EligibilityList;
use Alma\API\Exception\Endpoint\EligibilityEndpointException;

class EligibilityService implements EligibilityServiceInterface {

	private EligibilityEndpoint $eligibilityEndpoint;

	/** @var EligibilityList */
	private EligibilityList $eligibilityList;

	/** @var CartAdapterInterface */
	private CartAdapterInterface $cartAdapter;

	public function __construct( EligibilityEndpoint $eligibilityEndpoint, CartAdapterInterface $cartAdapter ) {

		$this->eligibilityEndpoint = $eligibilityEndpoint;
		$this->cartAdapter         = $cartAdapter;
	}

	/**
	 * Retrieve the eligibility list based on the current cart total.
	 *
	 * @throws EligibilityServiceException
	 */
	public function retrieveEligibility(): void {
		try {
			$purchase_amount = $this->cartAdapter->getCartTotal();
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
	 * @throws EligibilityServiceException
	 */
	public function getEligibilityList(): EligibilityList {
		if ( ! isset( $this->eligibilityList ) ) {
			$this->retrieveEligibility();
		}

		return $this->eligibilityList;
	}
}
