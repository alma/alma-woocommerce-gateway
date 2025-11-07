<?php

namespace Alma\Gateway\Application\Provider;

use Alma\API\Domain\Adapter\FeePlanListInterface;
use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\API\Domain\Port\FeePlanProviderInterface;
use Alma\API\Domain\ValueObject\PaymentMethod;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\MerchantEndpointException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;

class FeePlanProvider implements FeePlanProviderInterface, ProviderInterface {

	/** @var MerchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var FeePlanList */
	private FeePlanList $feePlanList;


	public function __construct( MerchantEndpoint $merchantEndpoint ) {
		$this->merchantEndpoint = $merchantEndpoint;
	}

	/**
	 * Get the fee plan list.
	 *
	 * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanList
	 * @throws FeePlanServiceException
	 */
	public function getFeePlanList( bool $forceRefresh = false ): FeePlanList {
		if ( ! isset( $this->feePlanList ) || $forceRefresh ) {
			$this->feePlanList = $this->retrieveFeePlanList();
		}

		return $this->feePlanList;
	}

	/**
	 * Retrieve the fee plan list from the merchant endpoint.
	 * @throws FeePlanServiceException
	 */
	private function retrieveFeePlanList(): FeePlanListInterface {

		try {
			return $this->merchantEndpoint->getFeePlanList( FeePlan::KIND_GENERAL, 'all', true )
			                              ->filterFeePlanList( array(
				                              PaymentMethod::CREDIT,
				                              PaymentMethod::PAY_LATER,
				                              PaymentMethod::PAY_NOW,
				                              PaymentMethod::PNX
			                              ) );

		} catch ( MerchantEndpointException $e ) {
			throw new FeePlanServiceException( 'Error retrieving fee plans: ' . $e->getMessage() );
		}
	}
}
