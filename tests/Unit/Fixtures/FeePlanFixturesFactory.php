<?php

namespace Alma\Gateway\Tests\Unit\Fixtures;

use Alma\Client\Application\Exception\ParametersException;
use Alma\Client\Domain\Entity\FeePlan;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;

class FeePlanFixturesFactory {

	/**
	 * Returns a FeePlanAdapter for a 3x installment plan.
	 *
	 * @param bool $enabled True to enable the plan, false otherwise
	 * @param int  $minAmount Override minimum amount
	 * @param int  $maxAmount Override maximum amount
	 *
	 * @return FeePlanAdapter
	 *
	 * @throws ParametersException
	 */
	public function getP2x( bool $enabled = true, int $minAmount = 5000, int $maxAmount = 200000 ): FeePlanAdapter {

		$feePlan = new FeePlan( [
			'allowed'               => true,
			'available_online'      => true,
			'customer_fee_variable' => 0,
			'deferred_days'         => 0,
			'deferred_months'       => 0,
			'installments_count'    => 2,
			'kind'                  => 'general',
			'max_purchase_amount'   => 200000,
			'merchant_fee_variable' => 0,
			'merchant_fee_fixed'    => 0,
			'min_purchase_amount'   => 5000,
		] );

		return $this->getFeePlanAdapter( $feePlan, $enabled, $minAmount, $maxAmount );
	}

	/**
	 * Returns a 3 installments fee plan adapter.
	 *
	 * @param bool $enabled True to enable the plan, false otherwise
	 * @param int  $minAmount Override minimum amount
	 * @param int  $maxAmount Override maximum amount
	 *
	 * @return FeePlanAdapter
	 *
	 * @throws ParametersException
	 */
	public function getP3x( bool $enabled = true, int $minAmount = 5000, int $maxAmount = 200000 ): FeePlanAdapter {

		$feePlan = new FeePlan( [
			'allowed'               => false,
			'available_online'      => true,
			'customer_fee_variable' => 0,
			'deferred_days'         => 0,
			'deferred_months'       => 0,
			'installments_count'    => 3,
			'kind'                  => 'general',
			'max_purchase_amount'   => 200000,
			'merchant_fee_variable' => 0,
			'merchant_fee_fixed'    => 0,
			'min_purchase_amount'   => 5000,
		] );

		return $this->getFeePlanAdapter( $feePlan, $enabled, $minAmount, $maxAmount );
	}

	/**
	 * Creates a FeePlanAdapter with the given parameters.
	 *
	 * @throws ParametersException
	 */
	private function getFeePlanAdapter( FeePlan $feePlan, bool $enabled = true, int $minAmount = 5000, int $maxAmount = 200000 ): FeePlanAdapter {
		$feePlanAdapter = new FeePlanAdapter( $feePlan );
		if ( $enabled ) {
			$feePlanAdapter->enable();
		}
		if ( $minAmount ) {
			$feePlanAdapter->setOverrideMinPurchaseAmount( $minAmount );
		}
		if ( $maxAmount ) {
			$feePlanAdapter->setOverrideMaxPurchaseAmount( $maxAmount );
		}

		return $feePlanAdapter;
	}
}
