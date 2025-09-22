<?php

namespace Alma\Gateway\Tests\Unit\Mocks;

use Alma\API\Domain\Entity\FeePlan;

class FeePlanMock {
	public static function getFeePlan(): FeePlan {
		return new FeePlan(
			[
				'allowed'               => true,
				'available_online'      => true,
				'customer_fee_variable' => 1.6,
				'deferred_days'         => 0,
				'deferred_months'       => 0,
				'installments_count'    => 3,
				'kind'                  => 'general',
				'max_purchase_amount'   => 100000,
				'merchant_fee_variable' => 1.3,
				'merchant_fee_fixed'    => 2.1,
				'min_purchase_amount'   => 5000,
			]
		);
	}
}