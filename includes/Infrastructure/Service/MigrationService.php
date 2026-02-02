<?php

namespace Alma\Gateway\Infrastructure\Service;

class MigrationService {

	const VERSION_KEY = 'alma_version';
	const OLD_KEY = 'wc_alma_settings';
	const NEW_KEY = 'woocommerce_alma_config_gateway_settings';
	const MIGRATION_LOCK = 'alma_migration_lock';

	const VERSION_6_0_0 = '6.0.0';

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
			return false; // Fresh install, no migrations needed
		}

		// Compare to the current plugin version. If different, run necessary migrations
		if ( version_compare( $version, self::VERSION_6_0_0, '<' ) ) {
			try {
				// Add a migration lock to prevent concurrent migrations
				add_option( self::MIGRATION_LOCK, time() );

				// Migration from version 5 to version 6
				$migratedData = $this->migrateFromV5ToV6( get_option( self::OLD_KEY, [] ) );

				// If merchant_id is missing or empty, invalidate the API keys
				if ( ! array_key_exists( 'merchant_id', $migratedData ) || empty( $migratedData['merchant_id'] ) ) {
					unset( $migratedData['live_api_key'] );
					unset( $migratedData['test_api_key'] );
				}

				add_option( self::NEW_KEY, $migratedData );

				// Update the version in the database
				update_option( self::VERSION_KEY, self::VERSION_6_0_0 );
			} finally {
				// Delete the migration lock
				delete_option( self::MIGRATION_LOCK );
			}

			return true;
		}

		return false;
	}

	/**
	 * Migrate data from version 5 to version 6.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated data for version 6.
	 */
	public function migrateFromV5ToV6( array $originData ): array {
		$migratedData = array_merge(
			$this->migrateBasicSettings( $originData ),
			$this->migrateDescriptions( $originData ),
			$this->migrateTitles( $originData ),
			$this->migrateWidgetSettings( $originData ),
			$this->migratePaymentPlans( $originData ),
			$this->migrateAmountLimits( $originData )
		);

		$migratedData['merchant_id']            = $originData['live_merchant_id'] ?? $originData['test_merchant_id'] ?? null;
		$migratedData['excluded_products_list'] = $this->migrateExcludedProducts( $originData );

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
			'debug'        => $originData['debug'] ?? 'no',
			'enabled'      => $originData['enabled'] ?? 'no',
			'live_api_key' => $originData['live_api_key'] ?? null,
			'test_api_key' => $originData['test_api_key'] ?? null,
			'environment'  => $originData['environment'] ?? 'test',
		];
	}

	/**
	 * Migrate description fields.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated descriptions.
	 */
	private function migrateDescriptions( array $originData ): array {
		return [
			'excluded_products_message'  => $originData['cart_not_eligible_message_gift_cards'] ?? null,
			'pnx_description_field'      => $this->getFirstAvailableValue( $originData, [
				'description_alma',
				'description_alma_in_page',
				'description_blocks_alma',
				'description_blocks_alma_in_page'
			] ),
			'paylater_description_field' => $this->getFirstAvailableValue( $originData, [
				'description_alma_pay_later',
				'description_alma_in_page_pay_later',
				'description_blocks_alma_pay_later',
				'description_blocks_alma_in_page_pay_later'
			] ),
			'paynow_description_field'   => $this->getFirstAvailableValue( $originData, [
				'description_alma_pay_now',
				'description_alma_in_page_pay_now',
				'description_blocks_alma_pay_now',
				'description_blocks_alma_in_page_pay_now'
			] ),
			'credit_description_field'   => $this->getFirstAvailableValue( $originData, [
				'description_alma_pnx_plus_4',
				'description_alma_in_page_pnx_plus_4',
				'description_blocks_alma_pnx_plus_4',
				'description_blocks_alma_in_page_pnx_plus_4'
			] ),
		];
	}

	/**
	 * Migrate title fields.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated titles.
	 */
	private function migrateTitles( array $originData ): array {
		return [
			'pnx_title_field'      => $this->getFirstAvailableValue( $originData, [
				'title_alma',
				'title_alma_in_page',
				'title_blocks_alma',
				'title_blocks_alma_in_page'
			] ),
			'paylater_title_field' => $this->getFirstAvailableValue( $originData, [
				'title_alma_pay_later',
				'title_alma_in_page_pay_later',
				'title_blocks_alma_pay_later',
				'title_blocks_alma_in_page_pay_later'
			] ),
			'paynow_title_field'   => $this->getFirstAvailableValue( $originData, [
				'title_alma_pay_now',
				'title_alma_in_page_pay_now',
				'title_blocks_alma_pay_now',
				'title_blocks_alma_in_page_pay_now'
			] ),
			'credit_title_field'   => $this->getFirstAvailableValue( $originData, [
				'title_alma_pnx_plus_4',
				'title_alma_in_page_pnx_plus_4',
				'title_blocks_alma_pnx_plus_4',
				'title_blocks_alma_in_page_pnx_plus_4'
			] ),
		];
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
			$result["general_{$plan}_min_amount"] = $originData["min_amount_general_{$plan}"] ?? null;
			$result["general_{$plan}_max_amount"] = $originData["max_amount_general_{$plan}"] ?? null;
		}

		return $result;
	}

	/**
	 * Migrate excluded products list from slugs to term IDs.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array|null The migrated excluded products list or null.
	 */
	private function migrateExcludedProducts( array $originData ): ?array {
		$excludedProducts = $originData['excluded_products_list'] ?? null;

		if ( ! is_array( $excludedProducts ) || empty( $excludedProducts ) ) {
			return null;
		}

		$termIds = [];
		foreach ( $excludedProducts as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$termIds[] = (string) $term->term_id;
			}
		}

		return empty( $termIds ) ? null : $termIds;
	}

	/**
	 * Get the first available value from the data array based on the provided keys.
	 *
	 * @param array $data The data array to search.
	 * @param array $keys The list of keys to check in order.
	 *
	 * @return ?string The first available value or null if none found.
	 */
	private function getFirstAvailableValue( array $data, array $keys ): ?string {
		foreach ( $keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				return $data[ $key ];
			}
		}

		return null;
	}
}
