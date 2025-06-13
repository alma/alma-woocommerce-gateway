<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\EligibilityEndpoint;
use Alma\API\Entities\EligibilityList;
use Alma\API\Exceptions\EligibilityServiceException;
use Alma\Gateway\Business\Service\LoggerService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

class EligibilityService {

	private EligibilityEndpoint $eligibility_endpoint;

	/** @var EligibilityList */
	private EligibilityList $eligibility_list;

	public function __construct( EligibilityEndpoint $eligibility_endpoint ) {

		$this->eligibility_endpoint = $eligibility_endpoint;
	}

	/**
	 * Retrieve the eligibility list based on the current cart total.
	 *
	 * @throws EligibilityServiceException
	 */
	public function retrieve_eligibility() {
		$logger = Plugin::get_container()->get( LoggerService::class );
		$logger->debug( '[EligibilityService] Retrieving eligibility list for cart total: ' . WooCommerceProxy::get_cart_total() );
		$this->eligibility_list = $this->eligibility_endpoint->getEligibilityList( array( 'purchase_amount' => WooCommerceProxy::get_cart_total() ) );
	}

	/**
	 * Get the eligibility list.
	 *
	 * @throws EligibilityServiceException
	 */
	public function get_eligibility_list(): EligibilityList {
		if ( ! isset( $this->eligibility_list ) ) {
			$this->retrieve_eligibility();
		}

		return $this->eligibility_list;
	}
}
