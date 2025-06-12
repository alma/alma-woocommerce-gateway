<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\MerchantEndpoint;
use Alma\API\Entities\FeePlanList;
use Alma\API\Exceptions\MerchantServiceException;

class FeePlanService {

	private MerchantEndpoint $merchant_endpoint;

	/** @var FeePlanList */
	private FeePlanList $fee_plan_list;

	public function __construct( MerchantEndpoint $merchant_endpoint ) {

		$this->merchant_endpoint = $merchant_endpoint;
	}

	/**
	 * Retrieve the fee plan list from the merchant endpoint.
	 *
	 * @throws MerchantServiceException
	 */
	public function retrieve_fee_plan_list() {

		$this->fee_plan_list = $this->merchant_endpoint->getFeePlanList();
	}

	/**
	 * Get the fee plan list.
	 *
	 * @throws MerchantServiceException
	 */
	public function get_fee_plan_list(): FeePlanList {
		if ( ! isset( $this->fee_plan_list ) ) {
			$this->retrieve_fee_plan_list();
		}

		return $this->fee_plan_list;
	}
}
