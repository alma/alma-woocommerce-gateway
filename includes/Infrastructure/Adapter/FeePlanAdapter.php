<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\FeePlanAdapterEligibilityAwareInterface;
use Alma\API\Domain\Adapter\FeePlanAdapterInterface;
use Alma\API\Domain\Adapter\FeePlanAdapterLocalConfigurationAwareInterface;
use Alma\API\Domain\Adapter\FeePlanInterface;
use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\PaymentPlanTrait;

/**
 * Adapter for Alma's FeePlan to implement FeePlanAdapterInterface.
 *
 * This class wraps around an instance of Alma\API\Domain\Entity\FeePlan
 *
 * @see FeePlanAdapterInterface
 *
 */
class FeePlanAdapter implements FeePlanInterface, FeePlanAdapterInterface, FeePlanAdapterLocalConfigurationAwareInterface, FeePlanAdapterEligibilityAwareInterface {

	/** Add ability to manage plan keys */
	use PaymentPlanTrait;

	/** Add ability to manage Eligibility data. */
	use FeePlanAdapterEligibilityAwareTrait;

	/** Add ability to manage local configuration. */
	use FeePlanAdapterLocalConfigurationAwareTrait;

	/**
	 * The original Fee Plan (from the API)
	 * @var FeePlan $almaFeePlan
	 */
	private FeePlan $almaFeePlan;

	public function __construct( FeePlan $almaFeePlan ) {
		$this->almaFeePlan = $almaFeePlan;
	}

	/**
	 * Check if this fee plan is allowed by Alma.
	 * @return bool
	 */
	public function isAllowed(): bool {
		return $this->almaFeePlan->isAllowed();
	}

	/**
	 * Check if this fee plan is:
	 * - allowed by Alma
	 * - enabled by the merchant
	 * @return bool
	 */
	public function isAvailable(): bool {
		return $this->almaFeePlan->isAvailable();
	}

	/**
	 * Check if this fee plan is available online.
	 * @return bool True if this fee plan is available online, false otherwise.
	 */
	public function isAvailableOnline(): bool {
		return $this->almaFeePlan->isAvailableOnline();
	}

	/**
	 * Get the minimum purchase amount allowed for this fee plan.
	 * @return int
	 */
	public function getMinPurchaseAmount(): int {
		return $this->almaFeePlan->getMinPurchaseAmount();
	}

	/**
	 * Get the maximum purchase amount allowed for this fee plan.
	 * @return int
	 */
	public function getMaxPurchaseAmount(): int {
		return $this->almaFeePlan->getMaxPurchaseAmount();
	}

	/**
	 * Get the number of deferred days this fee plan applies to.
	 * @return int The number of deferred days this fee plan applies to.
	 */
	public function getDeferredDays(): int {
		return $this->almaFeePlan->getDeferredDays();
	}

	/**
	 * Get the number of deferred months this fee plan applies to.
	 * @return int The number of deferred months this fee plan applies to.
	 */
	public function getDeferredMonths(): int {
		return $this->almaFeePlan->getDeferredMonths();
	}

	/**
	 * Get the installments count this fee plan applies to.
	 * @return int
	 */
	public function getInstallmentsCount(): int {
		return $this->almaFeePlan->getInstallmentsCount();
	}

	/**
	 * Get the Fixed Merchant Fees applied to this fee plan.
	 * @return int|null
	 */
	public function getMerchantFeeFixed(): int {
		return $this->almaFeePlan->getMerchantFeeFixed();
	}

	/**
	 * Get the Variable Merchant Fees applied to this fee plan.
	 * @return int|null
	 */
	public function getMerchantFeeVariable(): int {
		return $this->almaFeePlan->getMerchantFeeVariable();
	}

	/**
	 * Get the Variable Customer Fees applied to this fee plan.
	 * @return int|null
	 */
	public function getCustomerFeeVariable(): int {
		return $this->almaFeePlan->getCustomerFeeVariable();
	}

	/**
	 * Get the kind of payments this fee plan applies to.
	 * @return string
	 */
	public function getKind(): string {
		return $this->almaFeePlan->getKind();
	}

	/**
	 * Get the label this fee plan applies to.
	 * @return string
	 */
	public function getLabel(): string {
		return $this->almaFeePlan->getLabel();
	}
}
