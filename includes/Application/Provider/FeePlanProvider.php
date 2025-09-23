<?php

namespace Alma\Gateway\Application\Provider;

use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\API\Domain\Port\FeePlanProviderInterface;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\MerchantEndpointException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Service\ConfigService;

class FeePlanProvider implements FeePlanProviderInterface {

	/** @var MerchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var FeePlanList */
	private FeePlanList $feePlanList;

	/** @var ConfigService $configService */
	private ConfigService $configService;

	public function __construct( MerchantEndpoint $merchantEndpoint, ConfigService $configService ) {
		$this->merchantEndpoint = $merchantEndpoint;
		$this->configService    = $configService;
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
	private function retrieveFeePlanList(): FeePlanList {
		try {
			return $this->merchantEndpoint->getFeePlanList( FeePlan::KIND_GENERAL, 'all', true )
			                              ->filterFeePlanList( array( 'credit', 'pnx', 'pay-later', 'pay-now' ) );

		} catch ( MerchantEndpointException $e ) {
			throw new FeePlanServiceException( 'Error retrieving fee plans: ' . $e->getMessage() );
		}
	}
}
