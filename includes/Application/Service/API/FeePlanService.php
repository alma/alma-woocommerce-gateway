<?php

namespace Alma\Gateway\Application\Service\API;

use Alma\API\Endpoint\MerchantEndpoint;
use Alma\API\Entity\FeePlan;
use Alma\API\Entity\FeePlanList;
use Alma\API\Exception\Endpoint\MerchantEndpointException;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Exception\MerchantServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Service\OptionsService;

class FeePlanService {

	/** @var MerchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var FeePlanList */
	private FeePlanList $feePlanList;

	/** @var OptionsService $optionsService */
	private OptionsService $optionsService;

	public function __construct( MerchantEndpoint $merchantEndpoint, OptionsService $optionsService ) {
		$this->merchantEndpoint = $merchantEndpoint;
		$this->optionsService   = $optionsService;
	}

	/**
	 * Get the fee plan list.
	 *
	 * @param bool $forceRefresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanList
	 * @throws MerchantServiceException|ParametersException
	 */
	public function getFeePlanList( bool $forceRefresh = false ): FeePlanList {
		if ( ! isset( $this->feePlanList ) || $forceRefresh ) {
			$this->feePlanList = $this->setLocalConfiguration( $this->retrieveFeePlanList() );
		}

		return $this->feePlanList;
	}

	/**
	 * Retrieve the fee plan list from the merchant endpoint.
	 *
	 * @throws MerchantServiceException|ParametersException
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

			$this->optionsService->init_fee_plan_list( $feePlanList );

			return $feePlanList;

		} catch ( MerchantEndpointException $e ) {
			throw new MerchantServiceException( 'Error retrieving fee plans: ' . $e->getMessage() );
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
	 */
	private function setLocalConfiguration( FeePlanList $feePlanList ): FeePlanList {
		/** @var FeePlan $feePlan */
		foreach ( $feePlanList as $feePlan ) {
			if ( ! $this->optionsService->is_fee_plan_enabled( $feePlan->getPlanKey() ) ) {
				$feePlan->disable();
			}
			// WooCommerce use euros, but Alma API uses cents.
			$feePlan->setOverrideMaxPurchaseAmount( DisplayHelper::price_to_cent( $this->optionsService->get_max_amount( $feePlan->getPlanKey() ) ) );
			$feePlan->setOverrideMinPurchaseAmount( DisplayHelper::price_to_cent( $this->optionsService->get_min_amount( $feePlan->getPlanKey() ) ) );
		}

		return $feePlanList;
	}
}
