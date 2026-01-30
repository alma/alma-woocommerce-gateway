<?php

namespace Alma\Gateway\Application\Entity\Form;

use Alma\Client\Domain\ValueObject\Environment;
use Alma\Gateway\Application\Service\AuthenticationService;
use Alma\Gateway\Application\Service\ConfigService;

class KeyConfiguration {

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
	private string $newTestKey = '';

	/** @var string */
	private string $newLiveKey = '';

	/** @var string */
	private string $newMerchantId = '';

	/** @var string */
	private string $testMerchantId = '';

	/** @var string */
	private string $liveMerchantId = '';

	/** @var AuthenticationService */
	private AuthenticationService $authenticationService;

	/**
	 * ConfigForm constructor.
	 *
	 * @param ConfigService         $configService
	 * @param AuthenticationService $authenticationService
	 * @param string                $testApiKey
	 * @param string                $liveApiKey
	 */
	public function __construct( ConfigService $configService, AuthenticationService $authenticationService, string $testApiKey, string $liveApiKey ) {
		$this->configService         = $configService;
		$this->authenticationService = $authenticationService;

		// New config from form
		$this->newTestKey = $testApiKey;
		$this->newLiveKey = $liveApiKey;

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
	 * Get the new live key.
	 *
	 * @return string The new live key.
	 */
	public function getNewLiveKey(): string {
		return $this->newLiveKey;
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
	 * Validate the keys and retrieve the merchant id from API if possible.
	 * If both keys are set but from different accounts, reset both keys and merchant id to old values.
	 *
	 * @return KeyConfiguration The validated KeyConfiguration object.
	 */
	public function validate(): KeyConfiguration {

		// Get missing data from API
		$this->retrieveMerchantId();

		if ( ! empty( $this->testMerchantId ) && ! empty( $this->liveMerchantId ) ) {
			if ( $this->testMerchantId !== $this->liveMerchantId ) { // Both keys set but from different accounts
				$this->resetNewTestKey();
				$this->resetNewLiveKey();
				$this->resetNewMerchantId();
				$this->addError( 'Les clés API de test et de production ne proviennent pas du même compte.' );
			} else { // Two keys from the same account
				$this->newMerchantId = $this->testMerchantId;
			}
		} elseif ( ! empty( $this->testMerchantId ) ) {
			// Only one key
			$this->newMerchantId = $this->testMerchantId;
		} elseif ( ! empty( $this->liveMerchantId ) ) {
			// Only one key
			$this->newMerchantId = $this->liveMerchantId;
		} else {
			// But empty, reset all
			$this->resetNewTestKey();
			$this->resetNewLiveKey();
			$this->resetNewMerchantId();
		}

		return $this;
	}

	/**
	 * Check if the merchant id is different between database and form.
	 *
	 * @return bool True if the merchant id has changed, false otherwise.
	 */
	public function isMerchantIdChanged(): bool {
		// If the new merchant id is empty, it means that the keys or not set at all.
		if ( empty( $this->newMerchantId ) ) {
			return false;
		}

		return $this->newMerchantId !== $this->oldMerchantId;
	}

	/**
	 * Reset the new test key to the old test key or an empty string if not defined
	 * This is used when the key is invalid to avoid saving it in the database.
	 */
	private function resetNewTestKey(): void {
		$this->newTestKey = $this->oldTestKey ?? '';
	}

	/**
	 * Reset the new live key to the old live key or an empty string if not defined
	 * This is used when the key is invalid to avoid saving it in the database.
	 */
	private function resetNewLiveKey(): void {
		$this->newLiveKey = $this->oldLiveKey ?? '';
	}

	/**
	 * Reset the new merchant id to the old merchant id or an empty string if not defined
	 * This is used when the keys are invalid to avoid saving it in the database.
	 */
	private function resetNewMerchantId(): void {
		$this->newMerchantId = $this->oldMerchantId ?? '';
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

	/**
	 * Retrieve the merchant id associated with the new keys from API if not empty.
	 *
	 * @return void
	 */
	private function retrieveMerchantId() {
		if ( ! empty( $this->newTestKey ) ) {
			$this->testMerchantId = $this->authenticationService->checkAuthentication( $this->newTestKey,
				new Environment( Environment::TEST_MODE ) );
		}
		if ( ! empty( $this->newLiveKey ) ) {
			$this->liveMerchantId = $this->authenticationService->checkAuthentication( $this->newLiveKey,
				new Environment( Environment::LIVE_MODE ) );
		}
	}
}
