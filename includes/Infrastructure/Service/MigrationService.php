<?php

namespace Alma\Gateway\Infrastructure\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Helper\ContextHelper;

class MigrationService {

	const VERSION_KEY = 'alma_version';
	const OLD_KEY = 'wc_alma_settings';
	const NEW_KEY = 'woocommerce_alma_config_gateway_settings';
	const MIGRATION_LOCK = 'alma_migration_lock';

	const VERSION_6_0_0 = '6.0.0';
	const VERSION_6_0_7 = '6.0.7';

	const SOC_OPTION_ONGOING = 'alma_soc_ongoing';

	/**
	 * Run migrations if needed.
	 *
	 * @return bool True if migrations were run, false otherwise.
	 */
	public function runMigrationsIfNeeded(): bool {
		$lock = get_option( self::MIGRATION_LOCK, null );
		if ( $lock ) {
			return false; // Another migration is in progress
		}

		// Check if a version number exists in the database
		$version = get_option( self::VERSION_KEY, null );
		if ( ! $version ) {
			add_option( self::VERSION_KEY, self::VERSION_6_0_0 );

			return false; // Fresh install, no migrations needed
		}

		$migrated = false;

		// Compare to the current plugin version. If different, run necessary migrations
		if ( version_compare( $version, self::VERSION_6_0_0, '<' ) ) {
			try {
				// Add a migration lock to prevent concurrent migrations
				add_option( self::MIGRATION_LOCK, time() );

				$isBlocks = ContextHelper::isCheckoutPageUseBlocks();

				// Migration from version 5 to version 6
				$migratedData = $this->migrateFromV5ToV6( get_option( self::OLD_KEY, [] ), $isBlocks );

				// If merchant_id is missing or empty, invalidate the API keys
				if ( ! array_key_exists( 'merchant_id', $migratedData ) || empty( $migratedData['merchant_id'] ) ) {
					unset( $migratedData['live_api_key'] );
					unset( $migratedData['test_api_key'] );
				}

				add_option( self::NEW_KEY, $migratedData );

				// Update the version in the database
				update_option( self::VERSION_KEY, self::VERSION_6_0_0 );
				$version  = self::VERSION_6_0_0;
				$migrated = true;
			} finally {
				// Delete the migration lock
				delete_option( self::MIGRATION_LOCK );
			}
		}

		if ( version_compare( $version, self::VERSION_6_0_7, '<' ) ) {
			// Clean up the alma_soc_ongoing lock flag from wp_options (used by ShareOfCheckoutService in v5.3.0 to v5.16.2).
			// It may remain stuck if send_soc_data() crashed before the delete_option call.
			delete_option( self::SOC_OPTION_ONGOING );

			update_option( self::VERSION_KEY, self::VERSION_6_0_7 );
			$migrated = true;
		}

		return $migrated;
	}

	/**
	 * Migrate data from version 5 to version 6.
	 *
	 * @param array $originData The original data from version 5.
	 * @param bool  $isBlocks Whether the checkout blocks are enabled.
	 *
	 * @return array The migrated data for version 6.
	 */
	public function migrateFromV5ToV6( array $originData, bool $isBlocks = true ): array {
		$isInPage     = isset( $originData['display_in_page'] ) && $originData['display_in_page'] === 'yes';
		$migratedData = array_merge(
			$this->migrateBasicSettings( $originData ),
			$this->migrateDescriptions( $originData, $isBlocks, $isInPage ),
			$this->migrateTitles( $originData, $isBlocks, $isInPage ),
			$this->migrateWidgetSettings( $originData ),
			$this->migratePaymentPlans( $originData ),
			$this->migrateAmountLimits( $originData )
		);

		$migratedData['merchant_id']            = $originData['live_merchant_id'] ?? $originData['test_merchant_id'] ?? null;
		$migratedData['excluded_products_list'] = $originData['excluded_products_list'] ?? null;

		return array_filter( $migratedData, fn( $value ) => $value !== null );
	}

	/**
	 * Migrate basic settings.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated basic settings.
	 */
	private function migrateBasicSettings( array $originData ): array {
		return [
			'debug'                     => $originData['debug'] ?? 'no',
			'enabled'                   => $originData['enabled'] ?? 'no',
			'live_api_key'              => $originData['live_api_key'] ?? null,
			'test_api_key'              => $originData['test_api_key'] ?? null,
			'environment'               => $originData['environment'] ?? 'test',
			'excluded_products_message' => $originData['cart_not_eligible_message_gift_cards'] ?? null,
		];
	}

	/**
	 * Migrate description fields.
	 *
	 * @param array $originData The original data from version 5.
	 * @param bool  $isBlocks Whether the checkout blocks are enabled.
	 * @param bool  $isInPage Whether the in-page widget is enabled.
	 *
	 * @return array The migrated descriptions.
	 */
	private function migrateDescriptions( array $originData, bool $isBlocks, bool $isInPage ): array {
		return [
			'pnx_description_field'      => $originData[ $this->formatString( 'description', $isBlocks,
					$isInPage ) ] ?? null,
			'paylater_description_field' => $originData[ $this->formatString( 'description', $isBlocks, $isInPage,
					'pay_later' ) ] ?? null,
			'paynow_description_field'   => $originData[ $this->formatString( 'description', $isBlocks, $isInPage,
					'pay_now' ) ] ?? null,
			'credit_description_field'   => $originData[ $this->formatString( 'description', $isBlocks, $isInPage,
					'pnx_plus_4' ) ] ?? null,
		];
	}

	/**
	 * Migrate title fields.
	 *
	 * @param array $originData The original data from version 5.
	 * @param bool  $isBlocks Whether the checkout blocks are enabled.
	 * @param bool  $isInPage Whether the in-page widget is enabled.
	 *
	 * @return array The migrated titles.
	 */
	private function migrateTitles( array $originData, bool $isBlocks, bool $isInPage ): array {
		return [
			'pnx_title_field'      => $originData[ $this->formatString( 'title', $isBlocks, $isInPage ) ] ?? null,
			'paylater_title_field' => $originData[ $this->formatString( 'title', $isBlocks, $isInPage,
					'pay_later' ) ] ?? null,
			'paynow_title_field'   => $originData[ $this->formatString( 'title', $isBlocks, $isInPage,
					'pay_now' ) ] ?? null,
			'credit_title_field'   => $originData[ $this->formatString( 'title', $isBlocks, $isInPage,
					'pnx_plus_4' ) ] ?? null,
		];
	}

	/**
	 * Format the string based on the given parameters.
	 *
	 * @param string $base The base string to format.
	 * @param bool   $isBlocks Whether the checkout blocks are enabled.
	 * @param bool   $isInPage Whether the in-page widget is enabled.
	 * @param string $suffix An optional suffix to append to the string.
	 *
	 * @return string
	 */
	private function formatString( string $base, bool $isBlocks, bool $isInPage, string $suffix = '' ): string {
		$parts = [ $base ];

		if ( $isBlocks ) {
			$parts[] = 'blocks';
		}

		$parts[] = 'alma';

		if ( $isInPage ) {
			$parts[] = 'in_page';
		}

		if ( ! empty( $suffix ) ) {
			$parts[] = $suffix;
		}

		return implode( '_', $parts );
	}

	/**
	 * Migrate widget settings.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated widget settings.
	 */
	private function migrateWidgetSettings( array $originData ): array {
		return [
			'widget_cart_enabled'    => $originData['display_cart_eligibility'] ?? 'yes',
			'in_page_enabled'        => $originData['display_in_page'] ?? 'yes',
			'widget_product_enabled' => $originData['display_product_eligibility'] ?? 'yes',
		];
	}

	/**
	 * Migrate payment plans enabled status.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated payment plans.
	 */
	private function migratePaymentPlans( array $originData ): array {
		$plans  = [
			'6_0_0',
			'10_0_0',
			'12_0_0',
			'24_0_0',
			'1_0_0',
			'1_15_0',
			'1_30_0',
			'1_45_0',
			'2_0_0',
			'3_0_0',
			'4_0_0'
		];
		$result = [];

		foreach ( $plans as $plan ) {
			$oldKey            = "enabled_general_{$plan}";
			$newKey            = "general_{$plan}_enabled";
			$result[ $newKey ] = ( isset( $originData[ $oldKey ] ) && $originData[ $oldKey ] === 'yes' ) ? 1 : null;
		}

		return $result;
	}

	/**
	 * Migrate payment plans amount limits.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated amount limits.
	 */
	private function migrateAmountLimits( array $originData ): array {
		$plans  = [
			'6_0_0',
			'10_0_0',
			'12_0_0',
			'24_0_0',
			'1_0_0',
			'1_15_0',
			'1_30_0',
			'1_45_0',
			'2_0_0',
			'3_0_0',
			'4_0_0'
		];
		$result = [];

		foreach ( $plans as $plan ) {
			$result["general_{$plan}_min_amount"] = $originData["min_amount_general_{$plan}"] ?? 0;
			$result["general_{$plan}_max_amount"] = $originData["max_amount_general_{$plan}"] ?? 0;
		}

		return $result;
	}
}
