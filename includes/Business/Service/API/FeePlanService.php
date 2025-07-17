<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\MerchantEndpoint;
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\FeePlanList;
use Alma\API\Exceptions\Endpoint\MerchantEndpointException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

class FeePlanService {

	/** @var MerchantEndpoint */
	private MerchantEndpoint $merchant_endpoint;

	/** @var FeePlanList */
	private FeePlanList $fee_plan_list;

	/** @var OptionsService $options_service */
	private OptionsService $options_service;

	public function __construct( MerchantEndpoint $merchant_endpoint, OptionsService $options_service ) {
		$this->merchant_endpoint = $merchant_endpoint;
		$this->options_service   = $options_service;
	}

	/**
	 * Get the fee plan list.
	 *
	 * @param bool $force_refresh Whether to force a refresh of the fee plan list.
	 *
	 * @return FeePlanList
	 * @throws MerchantServiceException
	 */
	public function get_fee_plan_list( bool $force_refresh = false ): FeePlanList {
		if ( ! isset( $this->fee_plan_list ) || $force_refresh ) {
			$this->fee_plan_list = $this->set_local_configuration( $this->retrieve_fee_plan_list() );
		}

		return $this->fee_plan_list;
	}

	/**
	 * Retrieve the fee plan list from the merchant endpoint.
	 *
	 * @throws MerchantServiceException
	 */
	private function retrieve_fee_plan_list(): FeePlanList {
		try {
			$fee_plan_list = $this->merchant_endpoint->getFeePlanList( FeePlan::KIND_GENERAL, 'all', true )
													->filterFeePlanList(
														array(
															'credit',
															'pnx',
															'pay-later',
															'pay-now',
														)
													);

			$this->options_service->init_fee_plan_list( $fee_plan_list );

			return $fee_plan_list;

		} catch ( MerchantEndpointException $e ) {
			throw new MerchantServiceException( 'Error retrieving fee plans: ' . $e->getMessage() );
		}
	}

	/**
	 * Add merchant configurations to the fee plans
	 * This includes enabling/disabling plans and setting the min/max purchase amounts
	 * Based on the merchant's settings.
	 *
	 * @param FeePlanList $fee_plan_list
	 *
	 * @return FeePlanList
	 */
	private function set_local_configuration( FeePlanList $fee_plan_list ): FeePlanList {
		/** @var FeePlan $fee_plan */
		foreach ( $fee_plan_list as $fee_plan ) {
			if ( ! $this->options_service->is_fee_plan_enabled( $fee_plan->getPlanKey() ) ) {
				$fee_plan->disable();
			}
			// WooCommerce use euros, but Alma API uses cents.
			$fee_plan->setOverrideMaxPurchaseAmount( WooCommerceProxy::price_to_cent( $this->options_service->get_max_amount( $fee_plan->getPlanKey() ) ) );
			$fee_plan->setOverrideMinPurchaseAmount( WooCommerceProxy::price_to_cent( $this->options_service->get_min_amount( $fee_plan->getPlanKey() ) ) );
		}

		return $fee_plan_list;
	}
}
