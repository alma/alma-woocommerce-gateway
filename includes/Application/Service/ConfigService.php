<?php

namespace Alma\Gateway\Application\Service;

use Alma\Client\Application\DTO\PaymentDto;
use Alma\Client\Domain\ValueObject\Environment;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Infrastructure\Helper\WordPressHelper;
use Alma\Gateway\Plugin;
use Alma\Plugin\Infrastructure\Repository\ConfigRepositoryInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class ConfigService {

	/** @var string The merchant ID key */
	const MERCHANT_ID = 'merchant_id';

	/** @var string The live API key */
	const LIVE_API_KEY = 'live_api_key';

	/** @var string The test API key */
	const TEST_API_KEY = 'test_api_key';

	/** @var EncryptorHelper */
	private EncryptorHelper $encryptorHelper;

	/** @var ConfigRepositoryInterface */
	private ConfigRepositoryInterface $configRepository;

	/**
	 * @param EncryptorHelper           $encryptorHelper
	 * @param ConfigRepositoryInterface $configRepository
	 */
	public function __construct( EncryptorHelper $encryptorHelper, ConfigRepositoryInterface $configRepository ) {
		$this->encryptorHelper  = $encryptorHelper;
		$this->configRepository = $configRepository;

		// Define filters for encrypting keys
		WordPressHelper::set_key_encryptor();
	}

	/**
	 * Encrypt keys.
	 *
	 * @param $options array The whole posted settings.
	 */
	public static function encryptKeys( array $options ): array {

		/** @var EncryptorHelper $encryptor_helper */
		$encryptor_helper = Plugin::get_container()->get( EncryptorHelper::class );

		if ( ! empty( $options[ self::LIVE_API_KEY ] ) && strpos( $options[ self::LIVE_API_KEY ], 'sk_live_' ) === 0 ) {
			$options[ self::LIVE_API_KEY ] = $encryptor_helper->encrypt( $options[ self::LIVE_API_KEY ] );
		}

		if ( ! empty( $options[ self::TEST_API_KEY ] ) && strpos( $options[ self::TEST_API_KEY ], 'sk_test_' ) === 0 ) {
			$options[ self::TEST_API_KEY ] = $encryptor_helper->encrypt( $options[ self::TEST_API_KEY ] );
		}

		return $options;
	}

	/**
	 * Check if the plugin is configured.
	 *
	 * @return bool
	 */
	public function isConfigured(): bool {
		$is_configured = true;
		if ( empty( $this->getSettings() ) ) {
			$is_configured = false;
		}
		if ( $this->getActiveApiKey() === null || empty( $this->getActiveApiKey() ) ) {
			$is_configured = false;
		}

		return $is_configured;
	}

	/**
	 * Returns the active environment.
	 *
	 * @return Environment
	 */
	public function getEnvironment(): Environment {
		$mode = Environment::LIVE_MODE;
		if ( $this->hasSetting( 'environment' ) ) {
			$mode = $this->getSetting( 'environment' );
		}

		return Environment::fromString( $mode );
	}

	/**
	 * Are we using test environment?
	 *
	 * @return bool
	 */
	public function isTest(): bool {
		return $this->getEnvironment()->isTestMode();
	}

	/**
	 * Are we using live environment?
	 *
	 * @return bool
	 */
	public function isLive(): bool {
		return $this->getEnvironment()->isLiveMode();
	}

	/**
	 * Check if in-page is enabled.
	 *
	 * @return bool
	 */
	public function isInPageEnabled(): bool {
		return 'yes' === $this->getSetting( 'in_page_enabled' );
	}

	/**
	 * Return the Origin of the payment depending on if in-page is enabled or not.
	 *
	 * @return string
	 */
	public function getOrigin(): string {
		if ( $this->isInPageEnabled() ) {
			return PaymentDto::ORIGIN_ONLINE_IN_PAGE;
		}

		return PaymentDto::ORIGIN_ONLINE;
	}

	/**
	 * Check if we have keys for the active environment.
	 *
	 * @return bool
	 */
	public function hasKeys(): bool {
		if ( empty( $this->getActiveApiKey() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets API string for the current environment.
	 *
	 * @return string|null
	 */
	public function getActiveApiKey(): ?string {
		return $this->isLive() ? $this->getLiveApiKey() : $this->getTestApiKey();
	}

	/**
	 * Gets API key for live environment.
	 *
	 * @return string|null
	 */
	public function getLiveApiKey(): ?string {
		if ( isset( $this->getSettings()[ self::LIVE_API_KEY ] ) ) {
			return $this->encryptorHelper->decrypt( $this->getSettings()[ self::LIVE_API_KEY ] );
		}

		return null;
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string|null
	 */
	public function getTestApiKey(): ?string {
		if ( isset( $this->getSettings()[ self::TEST_API_KEY ] ) ) {
			return $this->encryptorHelper->decrypt( $this->getSettings()[ self::TEST_API_KEY ] );
		}

		return null;
	}

	/**
	 * Gets the enabled status
	 *
	 * @return bool True if the plugin is enabled, false otherwise
	 */
	public function isEnabled(): bool {
		if ( ! isset( $this->getSettings()['enabled'] ) ) {
			return false;
		}

		return 'yes' === $this->getSettings()['enabled'];
	}

	/**
	 * Gets the debug mode
	 *
	 * @return bool
	 */
	public function isDebug(): bool {
		if ( ! isset( $this->getSettings()['debug'] ) ) {
			return false;
		}

		return 'yes' === $this->getSettings()['debug'];
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasSetting( string $key ): bool {
		return $this->configRepository->hasSetting( $key );
	}

	/**
	 * @param string $key
	 *
	 * @return array|bool|string
	 */
	public function getSetting( string $key ) {
		return $this->configRepository->getSettings()[ $key ] ?? '';
	}

	/**
	 * Create a new key/value setting.
	 *
	 * @param string            $setting The setting key to create.
	 * @param array|bool|string $value The value for the setting.
	 *
	 * @return bool
	 */
	public function createSetting( string $setting, $value ): bool {
		return $this->configRepository->createSetting( $setting, $value );
	}

	/**
	 * Update a key/value setting.
	 *
	 * @param string            $setting The setting key to update.
	 * @param array|bool|string $value The new value for the setting.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function updateSetting( string $setting, $value ): bool {
		return $this->configRepository->updateSetting( $setting, $value );
	}

	/**
	 * Delete a key/value setting.
	 *
	 * @param $key string The setting key to delete.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function deleteSetting( string $key ): bool {
		return $this->configRepository->deleteSetting( $key );
	}

	/**
	 * Check if a Fee Plan is enabled in the options.
	 *
	 * @param string $fee_plan_key
	 *
	 * @return bool
	 */
	public function isFeePlanEnabled( string $fee_plan_key ): bool {
		$option = $fee_plan_key . '_enabled';

		return $this->getSetting( $option );
	}

	/**
	 * Get the maximum amount for a Fee Plan saved in config.
	 *
	 * @param string $feePlanKey
	 *
	 * @return int
	 */
	public function getMaxPurchaseAmount( string $feePlanKey ): int {
		return (int) $this->getSetting( $feePlanKey . '_max_amount' );
	}

	/**
	 * Get the minimum amount for a Fee Plan saved in config.
	 *
	 * @param string $feePlanKey
	 *
	 * @return int
	 */
	public function getMinPurchaseAmount( string $feePlanKey ): int {
		return (int) $this->getSetting( $feePlanKey . '_min_amount' );
	}

	/**
	 * Get all options.
	 *
	 * @return array
	 * @todo make private, public only for debugging purposes
	 */
	public function getSettings(): array {
		return $this->configRepository->getSettings();
	}

	/**
	 * Gets Merchant ID.
	 *
	 * @return string|null
	 */
	public function getMerchantId(): ?string {
		return $this->getSettings()[ self::MERCHANT_ID ] ?? null;
	}

	/**
	 * Get excluded categories.
	 *
	 * @return array The list of excluded categories.
	 */
	public function getExcludedCategories(): array {
		$excluded_categories = $this->getSetting( 'excluded_products_list' );
		if ( ! is_array( $excluded_categories ) ) {
			$excluded_categories = array();
		}

		return $excluded_categories;
	}

	/**
	 * Get excluded categories message.
	 *
	 * @return string The excluded categories message.
	 */
	public function getExcludedCategoriesMessage(): string {
		return $this->getSetting( 'excluded_products_message' );
	}

	/**
	 * Check if the cart widget is enabled.
	 *
	 * @return bool
	 */
	public function getWidgetCartEnabled(): bool {
		return 'yes' === $this->getSetting( 'widget_cart_enabled' );
	}

	/**
	 * Check if the product widget is enabled.
	 *
	 * @return bool
	 */
	public function getWidgetProductEnabled(): bool {
		return 'yes' === $this->getSetting( 'widget_product_enabled' );
	}

	/**
	 * Delete all Fee Plans configuration settings.
	 *
	 * @return void
	 */
	public function deleteFeePlansConfiguration(): void {
		$settings = $this->getSettings();
		foreach ( $settings as $key => $value ) {
			if ( preg_match( '/^general_.*(_min_amount|_max_amount|_enabled)$/', $key ) ) {
				$this->deleteSetting( $key );
			}
		}
	}
}
