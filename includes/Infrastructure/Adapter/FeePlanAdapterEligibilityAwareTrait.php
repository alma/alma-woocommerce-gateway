<?php

namespace Alma\Gateway\Infrastructure\Adapter;

trait FeePlanAdapterEligibilityAwareTrait {

	private int $customerTotalCostAmount = 0;
	private int $annualInterestRate = 0;
	private int $customerTotalCostBps = 0;
	private int $customerFee = 0;

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

	/**
	 * Get the customer total cost amount
	 * This Data comes from Eligibility call
	 * @return int
	 */
	public function getCustomerTotalCostAmount(): int {
		return $this->customerTotalCostAmount;
	}

	/**
	 * Set the customer total cost amount
	 * This Data comes from Eligibility call
	 *
	 * @param int $customerTotalCostAmount
	 *
	 * @return void
	 */
	public function setCustomerTotalCostAmount( int $customerTotalCostAmount ) {
		$this->customerTotalCostAmount = $customerTotalCostAmount;
	}

	/**
	 * Get the annual interest rate
	 * This Data comes from Eligibility call
	 * @return int
	 */
	public function getAnnualInterestRate(): int {
		return $this->annualInterestRate;
	}

	/**
	 * Set the annual interest rate
	 * This Data comes from Eligibility call
	 *
	 * @param int $annualInterestRate
	 *
	 * @return void
	 */
	public function setAnnualInterestRate( int $annualInterestRate ) {
		$this->annualInterestRate = $annualInterestRate;
	}

	/**
	 * Get the customer total cost bps
	 * This Data comes from Eligibility call
	 * @return int
	 */
	public function getCustomerTotalCostBps(): int {
		return $this->customerTotalCostBps;
	}

	/**
	 * Set the customer total cost bps
	 * This Data comes from Eligibility call
	 *
	 * @return void
	 */
	public function setCustomerTotalCostBps( int $customerTotalCostBps ) {
		$this->customerTotalCostBps = $customerTotalCostBps;
	}

	/**
	 * Get the customer fee
	 * This Data comes from Eligibility call
	 * @return int
	 */
	public function getCustomerFee(): int {
		return $this->customerFee;
	}

	/**
	 * Set the customer fee
	 * This Data comes from Eligibility call
	 *
	 * @param int $customerFee
	 *
	 * @return void
	 */
	public function setCustomerFee( int $customerFee ) {
		$this->customerFee = $customerFee;
	}

	/**
	 * Set the Eligibility.
	 * This Data comes from Eligibility call
	 *
	 * @param bool $eligibility
	 *
	 * @return void
	 */
	public function setEligibility( bool $eligibility ): void {
		$this->eligibility = $eligibility;
	}

	/**
	 * Define if the Fee Plan is Eligible or not.
	 * It must be Eligible, Available and in the boundaries.
	 * Override the default method to check the min and max overrides.
	 *
	 * @param int|null $purchaseAmount
	 *
	 * @return bool
	 */
	public function isEligible( ?int $purchaseAmount = null ): bool {
		if ( ( ! $this->isAvailable() ) || ( ! $this->eligibility ) ) {
			return false;
		}

		// If the purchase amount is below the minimum override or above the maximum override, it is not eligible
		if (
			! is_null( $purchaseAmount ) && (
				$purchaseAmount < $this->getOverrideMinPurchaseAmount() ||
				$purchaseAmount > $this->getOverrideMaxPurchaseAmount()
			)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Return the PaymentPlan
	 * This Data comes from Eligibility call
	 * @return array
	 */
	public function getPaymentPlan(): array {
		return $this->paymentPlan;
	}

	/**
	 * Add Payment Plans to Fee Plans
	 * This Data comes from Eligibility call
	 *
	 * @param array $paymentPlan
	 *
	 * @return void
	 */
	public function setPaymentPlan( array $paymentPlan ): void {
		$this->paymentPlan = $paymentPlan;
	}
}
