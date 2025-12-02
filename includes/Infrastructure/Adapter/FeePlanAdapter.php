<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\FeePlanAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanInterface;
use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\PaymentPlanTrait;
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

	use PaymentPlanTrait;

	/**
	 * The original Fee Plan (from the API)
	 * @var FeePlan $almaFeePlan
	 */
	private FeePlan $almaFeePlan;

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

	/**
	 * The Eligibility of the Fee Plan
	 * @var bool $eligibility
	 */
	private bool $eligibility = false;

	/**
	 * The payment plan for this fee plan.
	 * This comes from API.
	 * @var array $paymentPlan
	 */
	private array $paymentPlan = array();
	private int $customerTotalCostAmount = 0;
	private int $annualInterestRate = 0;
	private int $customerTotalCostBps = 0;
	private int $customerFee = 0;

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

	public function resetOverrideMinPurchaseAmount(): void {
		$this->overrideMinPurchaseAmount = $this->getMinPurchaseAmount();
	}

	public function resetOverrideMaxPurchaseAmount(): void {
		$this->overrideMaxPurchaseAmount = $this->getMaxPurchaseAmount();
	}

	/**
	 * Returns the unique plan key for this fee plan.
	 *
	 * @return string
	 */
	public function getPlanKey(): string {
		return $this->almaFeePlan->getPlanKey();
	}

	public function getLabel(): string {
		if ( $this->isPayNow() ) {
			return 'Pay now';
		} elseif ( $this->isPayLaterOnly() ) {
			return sprintf( '+%d', $this->getDeferredDays() );
		} else {
			return sprintf( '%dx', $this->getInstallmentsCount() );
		}
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

	public function setEligibility( bool $eligibility ): void {
		$this->eligibility = $eligibility;
	}

	/**
	 * Define if the Fee Plan is Eligible or not.
	 * It must be Eligible, Available and in the boundaries.
	 *
	 * @param int $purchaseAmount
	 *
	 * @return bool
	 */
	public function isEligible( int $purchaseAmount ): bool {
		if ( ( ! $this->isAvailable() ) || ( ! $this->eligibility ) ) {
			return false;
		}

		// If the purchase amount is below the minimum override or above the maximum override, it is not eligible
		if (
			$purchaseAmount < $this->getOverrideMinPurchaseAmount() ||
			$purchaseAmount > $this->getOverrideMaxPurchaseAmount()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Return the PaymentPlan
	 * @return array
	 */
	public function getPaymentPlan(): array {
		return $this->paymentPlan;
	}

	/**
	 * Add Payment Plans to Fee Plans
	 *
	 * @param array $paymentPlan
	 *
	 * @return void
	 */
	public function setPaymentPlan( array $paymentPlan ): void {
		$this->paymentPlan = $paymentPlan;
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

	public function getMerchantFeeFixed(): int {
		return $this->almaFeePlan->getMerchantFeeFixed();
	}

	public function getMerchantFeeVariable(): int {
		return $this->almaFeePlan->getMerchantFeeVariable();
	}

	public function getCustomerFeeVariable(): int {
		return $this->almaFeePlan->getCustomerFeeVariable();
	}

	public function getKind(): string {
		return $this->almaFeePlan->getKind();
	}

	public function getCustomerTotalCostAmount(): int {
		return $this->customerTotalCostAmount;
	}

	public function setCustomerTotalCostAmount( int $customerTotalCostAmount ) {
		$this->customerTotalCostAmount = $customerTotalCostAmount;
	}

	public function getAnnualInterestRate(): int {
		return $this->annualInterestRate;
	}

	public function setAnnualInterestRate( int $annualInterestRate ) {
		$this->annualInterestRate = $annualInterestRate;
	}

	public function getCustomerTotalCostBps(): int {
		return $this->customerTotalCostBps;
	}

	public function setCustomerTotalCostBps( int $customerTotalCostBps ) {
		$this->customerTotalCostBps = $customerTotalCostBps;
	}

	public function getCustomerFee(): int {
		return $this->customerFee;
	}

	public function setCustomerFee( int $customerFee ) {
		$this->customerFee = $customerFee;
	}
}
