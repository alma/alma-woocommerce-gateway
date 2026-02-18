<?php

namespace Alma\Gateway\Infrastructure\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\Exception\ParametersException;

trait FeePlanAdapterLocalConfigurationAwareTrait {

	/**
	 * The override min purchase amount (from the merchant config)
	 * @var int $overrideMinPurchaseAmount
	 */
	private int $overrideMinPurchaseAmount;

	/**
	 * The override max purchase amount (from the merchant config)
	 * @var int $overrideMaxPurchaseAmount
	 */
	private int $overrideMaxPurchaseAmount;

	/** @var bool Is this fee plan enabled by merchant? False by default */
	private bool $enabled = false;

	/**
	 * Enable this fee plan.
	 * @return void
	 */
	public function enable(): void {
		$this->enabled = true;
	}

	/**
	 * Check if this fee plan is:
	 * - enabled by the merchant.
	 * @return bool
	 */
	public function isEnabled(): bool {
		return $this->enabled;
	}

	/**
	 * Get the minimum purchase amount allowed for this fee plan.
	 *
	 * @return int
	 */
	public function getOverrideMinPurchaseAmount(): int {
		return $this->overrideMinPurchaseAmount ?? $this->getMinPurchaseAmount();
	}

	/**
	 * Set a local override to the minimum purchase amount allowed for this fee plan.
	 *
	 * @param int $overrideMinPurchaseAmount Amount in cents
	 *
	 * @return void
	 * @throws ParametersException
	 */
	public function setOverrideMinPurchaseAmount( int $overrideMinPurchaseAmount ): void {
		// If the config is too low, let's just set it to the min allowed by Alma
		if ( $overrideMinPurchaseAmount < $this->getMinPurchaseAmount() ) {
			$this->overrideMinPurchaseAmount = $this->getMinPurchaseAmount();
			throw new ParametersException( 'The minimum purchase amount cannot be lower than the minimum allowed by Alma.' );
		}

		// If the config is higher than the min override, let's just set it to the min allowed by Alma
		if ( $overrideMinPurchaseAmount > $this->getOverrideMaxPurchaseAmount() ) {
			$this->overrideMinPurchaseAmount = $this->getMinPurchaseAmount();
			throw new ParametersException( 'The minimum purchase amount cannot be higher than the maximum.' );
		}

		$this->overrideMinPurchaseAmount = $overrideMinPurchaseAmount;
	}

	/**
	 * Get the maximum purchase amount allowed for this fee plan.
	 *
	 * @return int
	 */
	public function getOverrideMaxPurchaseAmount(): int {
		return $this->overrideMaxPurchaseAmount ?? $this->getMaxPurchaseAmount();
	}

	/**
	 * Set a local override to the maximum purchase amount allowed for this fee plan.
	 *
	 * @param int $overrideMaxPurchaseAmount Amount in cents
	 *
	 * @return void
	 * @throws ParametersException
	 */
	public function setOverrideMaxPurchaseAmount( int $overrideMaxPurchaseAmount ): void {
		// If the config is too high, let's just set it to the max allowed by Alma
		if ( $overrideMaxPurchaseAmount > $this->getMaxPurchaseAmount() ) {
			$this->overrideMaxPurchaseAmount = $this->getMaxPurchaseAmount();
			throw new ParametersException( 'The maximum purchase amount cannot be higher than the maximum allowed by Alma.' );
		}

		// If the config is lower than the min override, let's just set it to the max allowed by Alma
		if ( $overrideMaxPurchaseAmount < $this->getOverrideMinPurchaseAmount() ) {
			$this->overrideMaxPurchaseAmount = $this->getMaxPurchaseAmount();
			throw new ParametersException( 'The minimum purchase amount cannot be higher than the maximum.' );
		}

		$this->overrideMaxPurchaseAmount = $overrideMaxPurchaseAmount;
	}

	/**
	 * Reset the override min value to its default.
	 * @return void
	 */
	public function resetOverrideMinPurchaseAmount(): void {
		$this->overrideMinPurchaseAmount = $this->getMinPurchaseAmount();
	}

	/**
	 * Reset the override max value to its default.
	 * @return void
	 */
	public function resetOverrideMaxPurchaseAmount(): void {
		$this->overrideMaxPurchaseAmount = $this->getMaxPurchaseAmount();
	}
}
