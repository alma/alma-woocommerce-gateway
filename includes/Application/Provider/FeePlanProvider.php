<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Client\Domain\Entity\FeePlan;
use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Client\Domain\ValueObject\PaymentMethod;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Plugin\Application\Port\FeePlanProviderInterface;
use Alma\Plugin\Infrastructure\Adapter\FeePlanListInterface;

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
			return new FeePlanList();
		}
	}
}
