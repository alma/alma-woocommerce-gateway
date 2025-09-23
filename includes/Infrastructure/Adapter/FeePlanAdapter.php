<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\FeePlanAdapterInterface;
use Alma\API\Domain\Entity\FeePlan;
use BadMethodCallException;

/**
 * Adapter for Alma's FeePlan to implement FeePlanAdapterInterface.
 *
 * This class wraps around an instance of Alma\API\Domain\Entity\FeePlan
 *
 * @see FeePlanAdapterInterface
 *
 * @method enable() : void see FeePlan::enable()
 * @method getCustomerFeeVariable(): ?int see FeePlan::getCustomerFeeVariable()
 * @method getDeferredDays(): int see FeePlan::getDeferredDays()
 * @method getDeferredMonths(): int see FeePlan::getDeferredMonths()
 * @method getInstallmentsCount(): int see FeePlan::getInstallmentsCount()
 * @method getKind(): string see FeePlan::getKind()
 * @method getMerchantFeeFixed(): ?int see FeePlan::getMerchantFeeFixed()
 * @method getMerchantFeeVariable(): ?int see FeePlan::getMerchantFeeVariable()
 * @method isAllowed(): bool see FeePlan::isAllowed()
 * @method isAvailable(): bool see FeePlan::isAvailable()
 * @method isAvailableOnline(): bool see FeePlan::isAvailableOnline()
 * @method isEligible( int $purchaseAmount ): bool see FeePlan::isEligible()
 * @method isEnabled(): bool see FeePlan::isEnabled()
 * @method getPlanKey(): string see FeePlan::getPlanKey()
 * @method getMinPurchaseAmount(): int see FeePlan::getMinPurchaseAmount()
 * @method getMaxPurchaseAmount(): int see FeePlan::getMaxPurchaseAmount()
 */
class FeePlanAdapter implements FeePlanAdapterInterface {

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
	 */
	public function setOverrideMinPurchaseAmount( int $overrideMinPurchaseAmount ): void {
		if ( $overrideMinPurchaseAmount < $this->almaFeePlan->getMinPurchaseAmount() ) {
			// If the config is too low, let's just set it to the min allowed by Alma
			$this->overrideMinPurchaseAmount = $this->almaFeePlan->getMinPurchaseAmount();
			//throw new ParametersException( 'The minimum purchase amount cannot be lower than the minimum allowed by Alma.' );
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
	 */
	public function setOverrideMaxPurchaseAmount( int $overrideMaxPurchaseAmount ): void {
		if ( $overrideMaxPurchaseAmount > $this->almaFeePlan->getMaxPurchaseAmount() ) {
			// If the config is too high, let's just set it to the max allowed by Alma
			$this->overrideMaxPurchaseAmount = $this->almaFeePlan->getMaxPurchaseAmount();
			//throw new ParametersException( 'The minimum purchase amount cannot be lower than the minimum allowed by Alma.' );
		}
		$this->overrideMaxPurchaseAmount = $overrideMaxPurchaseAmount;
	}
}
