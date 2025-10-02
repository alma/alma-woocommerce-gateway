<?php

namespace Alma\Gateway\Application\Entity;

use Alma\Gateway\Application\Service\ConfigService;

class FeePlansConfigForm {

	public const FEE_PLAN_MIN_AMOUNT_KEY = 'min_amount';
	public const FEE_PLAN_MAX_AMOUNT_KEY = 'max_amount';
	public const FEE_PLAN_ENABLED_KEY = 'enabled';

	private array $errors = [];

	/** @var ConfigService */
	private ConfigService $configService;

	private array $feePlans = [];

	/**
	 * ConfigForm constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct( ConfigService $configService ) {
		$this->configService = $configService;
	}

	public function addFeePlan( string $key, int $minAmount, int $maxAmount, bool $enabled ): void {

		$this->feePlans[ $key ] = [
			self::FEE_PLAN_ENABLED_KEY    => $enabled,
			self::FEE_PLAN_MIN_AMOUNT_KEY => $minAmount,
			self::FEE_PLAN_MAX_AMOUNT_KEY => $maxAmount,
		];
	}

	/**
	 * Get the fee plans.
	 *
	 * @return array The fee plans.
	 */
	public function getFeePlans(): array {
		return $this->feePlans;
	}

	/**
	 * Set the fee plans.
	 *
	 * @param array $feePlans The fee plans.
	 *
	 * @return void
	 */
	public function setFeePlans( array $feePlans ): void {
		$this->feePlans = $feePlans;
	}

	/**
	 * Add an error message to the errors array.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	public function addError( string $message ): void {
		$this->errors[] = $message;
	}

	/**
	 * Get the errors.
	 *
	 * @return array The errors.
	 */
	public function getErrors(): array {
		return $this->errors;
	}
}
