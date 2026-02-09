<?php

namespace Alma\Gateway\Application\Entity\Form;

use Alma\Client\Application\Exception\ParametersException;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class FeePlanDataForm
 *
 * Represents the data structure for a fee plan form.
 * This is used a gateway to transfer data between the form and the FeePlanConfigForm which will check if Fee Plans are valid.
 */
class FeePlanConfiguration {

	public const FEE_PLAN_MIN_AMOUNT_KEY = 'min_amount';
	public const FEE_PLAN_MAX_AMOUNT_KEY = 'max_amount';
	public const FEE_PLAN_ENABLED_KEY = 'enabled';

	private array $errors = [];

	/** @var string */
	private string $planKey;

	/** @var int */
	private int $minAmount;

	/** @var int */
	private int $maxAmount;

	/** @var bool */
	private bool $enabled;

	/** @var LoggerInterface */
	private LoggerInterface $loggerService;

	/**
	 * FeePlanDataForm constructor.
	 *
	 * @param string             $planKey
	 * @param int                $minAmount
	 * @param int                $maxAmount
	 * @param bool               $enabled
	 * @param LoggerService|null $loggerService
	 */
	public function __construct( string $planKey, int $minAmount, int $maxAmount, bool $enabled, LoggerService $loggerService = null ) {
		$this->planKey       = $planKey;
		$this->minAmount     = $minAmount;
		$this->maxAmount     = $maxAmount;
		$this->enabled       = $enabled;
		$this->loggerService = $loggerService ?? new NullLogger();
	}

	/**
	 * Get the plan key.
	 *
	 * @return string The plan key.
	 */
	public function getPlanKey(): string {
		return $this->planKey;
	}

	/**
	 * Get the minimum amount.
	 *
	 * @return int The minimum amount.
	 */
	public function getMinAmount(): int {
		return $this->minAmount;
	}

	/**
	 * Get the maximum amount.
	 *
	 * @return int The maximum amount.
	 */
	public function getMaxAmount(): int {
		return $this->maxAmount;
	}

	/**
	 * Check if the fee plan is enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function isEnabled(): bool {
		return $this->enabled;
	}

	/**
	 * Validate and apply the fee plan configuration to the given FeePlanAdapter.
	 *
	 * @param FeePlanAdapter $feePlanAdapter
	 *
	 * @return void
	 */
	public function validate( FeePlanAdapter $feePlanAdapter ): void {
		if ( $feePlanAdapter->getPlanKey() === $this->getPlanKey() ) {
			// Set the min amount, or reset to default if invalid.
			try {
				$feePlanAdapter->setOverrideMinPurchaseAmount( $this->getMinAmount() );
			} catch ( ParametersException $e ) {
				$this->loggerService->error( 'Invalid minimum purchase amount for fee plan', [
					'planKey'   => $this->getPlanKey(),
					'minAmount' => $this->getMinAmount(),
					'exception' => $e,
				] );
				$this->addError( $e->getMessage() );
				$feePlanAdapter->resetOverrideMinPurchaseAmount();
			}
			// Set the max amount, or reset to default if invalid.
			try {
				$feePlanAdapter->setOverrideMaxPurchaseAmount( $this->getMaxAmount() );
			} catch ( ParametersException $e ) {
				$this->loggerService->error( 'Invalid maximum purchase amount for fee plan', [
					'planKey'   => $this->getPlanKey(),
					'maxAmount' => $this->getMaxAmount(),
					'exception' => $e,
				] );
				$this->addError( $e->getMessage() );
				$feePlanAdapter->resetOverrideMaxPurchaseAmount();
			}
			// Enable the fee plan if it's enabled in the form.
			if ( $this->isEnabled() ) {
				$feePlanAdapter->enable();
			}
		}
	}

	/**
	 * Get the errors array.
	 *
	 * @return array The errors array.
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Add an error message to the errors array.
	 * Message is used as key to avoid duplicates.
	 *
	 * @param string $message
	 *
	 * @return void
	 */
	private function addError( string $message ): void {
		$this->errors[ $message ] = $message;
	}
}
