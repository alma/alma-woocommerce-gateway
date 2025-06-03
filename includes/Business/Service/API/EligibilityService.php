<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\EligibilityEndpoint;
use Alma\API\Exceptions\EligibilityServiceException;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;

class EligibilityService {

	private static bool $is_eligible = false;
	private EligibilityEndpoint $eligibility_endpoint;

	public function __construct( EligibilityEndpoint $eligibility_endpoint ) {

		$this->eligibility_endpoint = $eligibility_endpoint;
	}

	/**
	 * @throws EligibilityServiceException
	 */
	public function init(): void {
		self::$is_eligible = $this->check_eligibility(
			array(
				'purchase_amount' => WooCommerceProxy::get_cart_total(),
			)
		);
	}

	public function is_eligible(): bool {
		return self::$is_eligible;
	}

	/**
	 * @throws EligibilityServiceException
	 */
	private function check_eligibility( $eligibility_data ): bool {

		$eligibility = $this->eligibility_endpoint->eligibility( $eligibility_data );
		foreach ( $eligibility as $eligibility_item ) {
			if ( $eligibility_item->isEligible() ) {
				return true;
			}
		}

		return false;
	}
}
