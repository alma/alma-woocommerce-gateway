<?php

namespace Alma\Gateway\Application\Service\API;

use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\API\Domain\Service\API\FeePlanServiceInterface;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\MerchantEndpointException;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Service\ConfigService;

class FeePlanService implements FeePlanServiceInterface {

	/** @var MerchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var FeePlanList */
	private FeePlanList $feePlanList;

	/** @var ConfigService $optionsService */
	private ConfigService $optionsService;

	public function __construct( MerchantEndpoint $merchantEndpoint, ConfigService $optionsService ) {
		$this->merchantEndpoint = $merchantEndpoint;
		$this->optionsService   = $optionsService;
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
			$this->feePlanList = $this->setLocalConfiguration( $this->retrieveFeePlanList() );
		}

		return $this->feePlanList;
	}

	/**
	 * Retrieve the fee plan list from the merchant endpoint.
	 * @throws FeePlanServiceException
	 */
	private function retrieveFeePlanList(): FeePlanList {
		try {
			$feePlanList = $this->merchantEndpoint->getFeePlanList( FeePlan::KIND_GENERAL, 'all', true )
			                                      ->filterFeePlanList(
				                                      array(
					                                      'credit',
					                                      'pnx',
					                                      'pay-later',
					                                      'pay-now',
				                                      )
			                                      );

			$this->optionsService->initFeePlanList( $feePlanList );

			return $feePlanList;

		} catch ( MerchantEndpointException $e ) {
			throw new FeePlanServiceException( 'Error retrieving fee plans: ' . $e->getMessage() );
		}
	}

	/**
	 * Add merchant configurations to the fee plans
	 * This includes enabling/disabling plans and setting the min/max purchase amounts
	 * Based on the merchant's settings.
	 *
	 * @param FeePlanList $feePlanList
	 *
	 * @return FeePlanList
	 * @throws FeePlanServiceException
	 */
	private function setLocalConfiguration( FeePlanList $feePlanList ): FeePlanList {
		/** @var FeePlan $feePlan */
		foreach ( $feePlanList as $feePlan ) {
			if ( $this->optionsService->isFeePlanEnabled( $feePlan->getPlanKey() ) ) {
				$feePlan->enable();
			}
			// WooCommerce use euros, but Alma API uses cents.
			try {
				$feePlan->setOverrideMaxPurchaseAmount( $this->optionsService->getMaxAmount( $feePlan->getPlanKey() ) );
				$feePlan->setOverrideMinPurchaseAmount( $this->optionsService->getMinAmount( $feePlan->getPlanKey() ) );
			} catch ( ParametersException $e ) {
				//throw new FeePlanServiceException( $e->getMessage() );
			}
		}

		return $feePlanList;
	}
}
