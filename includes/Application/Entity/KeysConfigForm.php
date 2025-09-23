<?php

namespace Alma\Gateway\Application\Entity;

use Alma\Gateway\Application\Service\ConfigService;

class KeysConfigForm {

	private array $errors = [];

	/** @var ConfigService */
	private ConfigService $configService;

	/** @var string */
	private string $oldTestKey;

	/** @var string */
	private string $oldLiveKey;

	/** @var string */
	private string $oldMerchantId;

	/** @var string */
	private string $newTestKey;

	/** @var string */
	private string $newLiveKey;

	/** @var string */
	private string $newMerchantId = '';


	/**
	 * ConfigForm constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct( ConfigService $configService ) {
		$this->configService = $configService;

		// Get old config
		$this->oldTestKey    = $this->configService->getTestApiKey() ?? '';
		$this->oldLiveKey    = $this->configService->getLiveApiKey() ?? '';
		$this->oldMerchantId = $this->configService->getMerchantId() ?? '';
	}

	/**
	 * Get the new test key.
	 *
	 * @return string The new test key.
	 */
	public function getNewTestKey(): string {
		return $this->newTestKey;
	}

	/**
	 * Set the new test key.
	 *
	 * @param string $newTestKey The new test key.
	 *
	 * @return KeysConfigForm
	 */
	public function setNewTestKey( string $newTestKey ): KeysConfigForm {
		$this->newTestKey = $newTestKey;

		return $this;
	}

	/**
	 * Reset the new test key to the old test key or an empty string if not defined
	 * This is used when the key is invalid to avoid saving it in the database.
	 */
	public function resetNewTestKey(): void {
		$this->newTestKey = $this->oldTestKey ?? '';
	}

	/**
	 * Get the new live key.
	 *
	 * @return string The new live key.
	 */
	public function getNewLiveKey(): string {
		return $this->newLiveKey;
	}

	/**
	 * Set the new live key.
	 *
	 * @param string $newLiveKey The new live key.
	 *
	 * @return KeysConfigForm
	 */
	public function setNewLiveKey( string $newLiveKey ): KeysConfigForm {
		$this->newLiveKey = $newLiveKey;

		return $this;
	}

	/**
	 * Reset the new live key to the old live key or an empty string if not defined
	 * This is used when the key is invalid to avoid saving it in the database.
	 */
	public function resetNewLiveKey(): void {
		$this->newLiveKey = $this->oldLiveKey ?? '';
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
	 * Check if the test API key has changed in the form.
	 *
	 * @return bool True if the test API key has changed, false otherwise.
	 */
	public function isTestKeyChanged(): bool {
		return $this->newTestKey !== $this->oldTestKey;
	}

	/**
	 * Check if the live API key has changed in the form.
	 *
	 * @return bool True if the live API key has changed, false otherwise.
	 */
	public function isLiveKeyChanged(): bool {
		return $this->newLiveKey !== $this->oldLiveKey;
	}

	/**
	 * Check if the new test API key is empty.
	 *
	 * @return bool True if the new test API key is empty, false otherwise.
	 */
	public function isNewTestKeyEmpty(): bool {
		return empty( $this->newTestKey );
	}

	/**
	 * Check if the new live API key is empty.
	 *
	 * @return bool True if the new live API key is empty, false otherwise.
	 */
	public function isNewLiveKeyEmpty(): bool {
		return empty( $this->newLiveKey );
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
	 * Get the new merchant id if both test and live keys are from the same account.
	 *
	 * @return string The new merchant id.
	 */
	public function getNewMerchantId(): string {
		return $this->newMerchantId;
	}

	/**
	 * Set the new merchant id if both test and live keys are from the same account.
	 *
	 * @param string $newTestMerchantId
	 * @param string $newLiveMerchantId
	 *
	 * @return void
	 */
	public function setNewMerchantId( string $newTestMerchantId, string $newLiveMerchantId ): void {
		if ( empty( $newTestMerchantId ) && empty( $newLiveMerchantId ) ) { // Two empty keys
			$this->newMerchantId = '';
		} elseif ( empty( $newTestMerchantId ) ) { // Live key set only
			$this->newMerchantId = $newLiveMerchantId;
		} elseif ( empty( $newLiveMerchantId ) ) { // Test key set only
			$this->newMerchantId = $newTestMerchantId;
		} elseif ( $newTestMerchantId !== $newLiveMerchantId ) { // Both keys set but from different accounts
			$this->resetNewTestKey();
			$this->resetNewLiveKey();
			$this->addError( 'Les clés API de test et de production ne proviennent pas du même compte.' );
		} else { // Two keys from the same account
			$this->newMerchantId = $newTestMerchantId;
		}
	}
}