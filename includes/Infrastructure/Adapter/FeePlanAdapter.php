<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\FeePlanAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanInterface;
use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Infrastructure\Exception\ParametersException;
use BadMethodCallException;

/**
 * Adapter for Alma's FeePlan to implement FeePlanAdapterInterface.
 *
 * This class wraps around an instance of Alma\API\Domain\Entity\FeePlan
 *
 * @see FeePlanAdapterInterface
 *
 */
class FeePlanAdapter implements FeePlanAdapterInterface, FeePlanInterface {

	private $almaFeePlan;
	private int $overrideMinPurchaseAmount;
	private int $overrideMaxPurchaseAmount;

	public function __construct( FeePlan $almaFeePlan ) {
		$this->almaFeePlan = $almaFeePlan;
	}

	/**
	 * Dynamic call to all FeePlan methods
	 */
	public function __call( string $name, array $arguments ) {

		if ( method_exists( $this->almaFeePlan, $name ) ) {
			return $this->almaFeePlan->{$name}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $name) does not exists on FeePlan" );
	}

	/**
	 * Get the minimum purchase amount allowed for this fee plan.
	 *
	 * @return int
	 */
	public function getOverrideMinPurchaseAmount(): int {
		return $this->overrideMinPurchaseAmount ?? $this->almaFeePlan->getMinPurchaseAmount();
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
		if ( $overrideMinPurchaseAmount < $this->almaFeePlan->getMinPurchaseAmount() ) {
			$this->overrideMinPurchaseAmount = $this->almaFeePlan->getMinPurchaseAmount();
			throw new ParametersException( 'The minimum purchase amount cannot be lower than the minimum allowed by Alma.' );
		}

		// If the config is higher than the min override, let's just set it to the min allowed by Alma
		if ( $overrideMinPurchaseAmount > $this->getOverrideMaxPurchaseAmount() ) {
			$this->overrideMinPurchaseAmount = $this->almaFeePlan->getMinPurchaseAmount();
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
		return $this->overrideMaxPurchaseAmount ?? $this->almaFeePlan->getMaxPurchaseAmount();
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
		if ( $overrideMaxPurchaseAmount > $this->almaFeePlan->getMaxPurchaseAmount() ) {
			$this->overrideMaxPurchaseAmount = $this->almaFeePlan->getMaxPurchaseAmount();
			throw new ParametersException( 'The maximum purchase amount cannot be higher than the maximum allowed by Alma.' );
		}

		// If the config is lower than the min override, let's just set it to the max allowed by Alma
		if ( $overrideMaxPurchaseAmount < $this->getOverrideMinPurchaseAmount() ) {
			$this->overrideMaxPurchaseAmount = $this->almaFeePlan->getMaxPurchaseAmount();
			throw new ParametersException( 'The minimum purchase amount cannot be higher than the maximum.' );
		}

		$this->overrideMaxPurchaseAmount = $overrideMaxPurchaseAmount;
	}

	public function resetOverrideMinPurchaseAmount(): void {
		$this->overrideMinPurchaseAmount = $this->almaFeePlan->getMinPurchaseAmount();
	}

	public function resetOverrideMaxPurchaseAmount(): void {
		$this->overrideMaxPurchaseAmount = $this->almaFeePlan->getMaxPurchaseAmount();
	}

	/**
	 * Returns the unique plan key for this fee plan.
	 *
	 * @return string
	 */
	public function getPlanKey(): string {
		return $this->almaFeePlan->getPlanKey();
	}

	/**
	 * Returns the minimum purchase amount this fee plan applies to.
	 *
	 * @return int
	 */
	public function getMinPurchaseAmount(): int {
		return $this->almaFeePlan->getMinPurchaseAmount();
	}

	/**
	 * Returns the maximum purchase amount this fee plan applies to.
	 *
	 * @return int
	 */
	public function getMaxPurchaseAmount(): int {
		return $this->almaFeePlan->getMaxPurchaseAmount();
	}

	/**
	 * Returns the number of installments this fee plan applies to.
	 *
	 * @return int
	 */
	public function getInstallmentsCount(): int {
		return $this->almaFeePlan->getInstallmentsCount();
	}

	/**
	 * Returns the number of deferred days this fee plan applies to.
	 *
	 * @return int
	 */
	public function getDeferredDays(): int {
		return $this->almaFeePlan->getDeferredDays();
	}

	/**
	 * Returns the number of deferred months this fee plan applies to.
	 *
	 * @return int
	 */
	public function getDeferredMonths(): int {
		return $this->almaFeePlan->getDeferredMonths();
	}

	public function isAllowed(): bool {
		return $this->almaFeePlan->isAllowed();
	}

	public function isEligible( int $purchaseAmount ): bool {
		return $this->almaFeePlan->isEligible( $purchaseAmount );
	}

	public function isEnabled(): bool {
		return $this->almaFeePlan->isEnabled();
	}

	public function enable(): void {
		$this->almaFeePlan->enable();
	}

	public function isAvailable(): bool {
		return $this->almaFeePlan->isAvailable();
	}

	public function isAvailableOnline(): bool {
		return $this->almaFeePlan->isAvailableOnline();
	}

	public function getMerchantFeeFixed(): ?int {
		return $this->almaFeePlan->getMerchantFeeFixed();
	}

	public function getMerchantFeeVariable(): ?int {
		return $this->almaFeePlan->getMerchantFeeVariable();
	}

	public function getCustomerFeeVariable(): ?int {
		return $this->almaFeePlan->getCustomerFeeVariable();
	}

	public function getKind(): string {
		return $this->almaFeePlan->getKind();
	}
}
