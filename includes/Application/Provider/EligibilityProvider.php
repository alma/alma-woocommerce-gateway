<?php

namespace Alma\Gateway\Application\Provider;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Domain\Port\EligibilityProviderInterface;
use Alma\API\Infrastructure\Endpoint\EligibilityEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\EligibilityEndpointException;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;

class EligibilityProvider implements EligibilityProviderInterface {

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
		$purchaseAmount = $this->cartAdapter->getCartTotal();

		if ( $purchaseAmount <= 0 ) {
			$this->eligibilityList = new EligibilityList();

			return;
		}
	
		try {
			$this->eligibilityList = $this->eligibilityEndpoint->getEligibilityList( array( 'purchase_amount' => $purchaseAmount ) );
		} catch ( EligibilityEndpointException $e ) {
			throw new EligibilityServiceException( 'Error retrieving eligibility: ' . $e->getMessage(), 0, $e );
		}
	}

	/**
	 * Get the eligibility list.
	 *
	 * @return EligibilityList
	 * @throws EligibilityServiceException
	 */
	public function getEligibilityList(): EligibilityList {
		if ( ! isset( $this->eligibilityList ) ) {
			$this->retrieveEligibility();
		}

		return $this->eligibilityList;
	}
}
