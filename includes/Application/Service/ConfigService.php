<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Exception\Service\ContainerServiceException;
use Alma\API\Domain\Repository\ConfigRepositoryInterface;
use Alma\API\Entity\FeePlan;
use Alma\API\Entity\FeePlanList;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Infrastructure\Helper\WordPressHelper;
use Alma\Gateway\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class ConfigService {

	/** @var string The live environment key */
	const ALMA_ENVIRONMENT_LIVE = 'live';

	/** @var string The test environment key */
	const ALMA_ENVIRONMENT_TEST = 'test';

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
	 *
	 * @throws ContainerServiceException
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
	 * @return string
	 */
	public function getEnvironment(): string {
		if ( isset( $this->getSettings()['environment'] ) ) {
			return self::ALMA_ENVIRONMENT_LIVE === $this->getSettings()['environment']
				? self::ALMA_ENVIRONMENT_LIVE : self::ALMA_ENVIRONMENT_TEST;
		}

		return self::ALMA_ENVIRONMENT_LIVE;
	}

	/**
	 * Are we using test environment?
	 *
	 * @return bool
	 */
	public function isTest(): bool {
		return $this->getEnvironment() === self::ALMA_ENVIRONMENT_TEST;
	}

	/**
	 * Are we using live environment?
	 *
	 * @return bool
	 */
	public function isLive(): bool {
		return $this->getEnvironment() === self::ALMA_ENVIRONMENT_LIVE;
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
	 * @param $key
	 *
	 * @return mixed|array|string
	 */
	public function getSetting( $key ) {
		return $this->configRepository->getSettings()[ $key ] ?? '';
	}

	public function hasSetting( $key ): bool {
		return $this->configRepository->hasSetting( $key );
	}

	/**
	 * Init Fee Plan list options with values from the Alma API.
	 *
	 * @param FeePlanList $fee_plan_list The given Fee Plan list to initialize.
	 *
	 * @return void
	 */
	public function initFeePlanList( FeePlanList $fee_plan_list ) {
		/** @var FeePlan $fee_plan */
		foreach ( $fee_plan_list as $fee_plan ) {

			$default_plan_list = array(
				'_enabled'    => false,
				'_max_amount' => DisplayHelper::price_to_euro( $fee_plan->getMaxPurchaseAmount() ),
				'_min_amount' => DisplayHelper::price_to_euro( $fee_plan->getMinPurchaseAmount() ),
			);

			foreach ( $default_plan_list as $plan_key => $default_value ) {
				$option_key = $fee_plan->getPlanKey() . $plan_key;
				if ( ! $this->hasSetting( $option_key ) ) {
					$this->configRepository->updateSetting( $option_key, $default_value );
				}
			}
		}
	}

	/**
	 * Toggle a Fee Plan status.
	 *
	 * @param string $fee_plan_key The ID of the Fee Plan to toggle.
	 *
	 * @return bool True if the Fee Plan is now enabled, false otherwise.
	 */
	public function toggleFeePlan( string $fee_plan_key ): bool {
		$option                  = $fee_plan_key . '_enabled';
		$current_fee_plan_status = $this->getSetting( $option );
		$new_fee_plan_status     = 'yes' === $current_fee_plan_status ? 'no' : 'yes';
		$this->configRepository->updateSetting( $option, $new_fee_plan_status );

		return 'yes' === $new_fee_plan_status;
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

		return $this->getSetting( $option ) === 'yes';
	}

	/**
	 * Get the maximum amount for a Fee Plan.
	 *
	 * @param string $fee_plan_key
	 *
	 * @return int
	 */
	public function getMaxAmount( string $fee_plan_key ): int {
		return (int) $this->getSetting( $fee_plan_key . '_max_amount' );
	}

	/**
	 * Get the minimum amount for a Fee Plan.
	 *
	 * @param string $fee_plan_key
	 *
	 * @return int
	 */
	public function getMinAmount( string $fee_plan_key ): int {
		return (int) $this->getSetting( $fee_plan_key . '_min_amount' );
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

	public function deleteSetting( string $key ): bool {

		return $this->configRepository->deleteSetting( $key );
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
		if ( ! empty( $excluded_categories ) ) {
			$excluded_categories = array_map( 'intval', $excluded_categories );
		}

		return $excluded_categories;
	}

	public function getWidgetCartEnabled(): bool {
		return 'yes' === $this->getSetting( 'widget_cart_enabled' );
	}

	public function getWidgetProductEnabled(): bool {
		return 'yes' === $this->getSetting( 'widget_product_enabled' );
	}
}
