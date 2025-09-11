<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\GatewayRepositoryInterface;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;

class GatewayRepository implements GatewayRepositoryInterface {

	/**
	 * Get all Alma gateways.
	 *
	 * @return array
	 */
	public function getAlmaGateways(): array {
		return array_filter(
			WC()->payment_gateways()->payment_gateways(),
			function ( $gateway ) {
				return $gateway instanceof AbstractGateway;
			}
		);
	}
}
