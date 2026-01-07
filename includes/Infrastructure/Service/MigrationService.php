<?php

namespace Alma\Gateway\Infrastructure\Service;

class MigrationService {

	const VERSION_KEY = 'alma_version';
	const OLD_KEY = 'wc_alma_settings';
	const NEW_KEY = 'woocommerce_alma_config_gateway_settings';
	const MIGRATION_LOCK = 'alma_migration_lock';

	const VERSION_6_0_0 = '6.0.0';

	public function runMigrationsIfNeeded() {
		$lock = get_option( self::MIGRATION_LOCK, null );
		if ( $lock ) {
			return; // Another migration is in progress
		}

		// Check if a version number exists in the database
		$version = get_option( self::VERSION_KEY, null );
		if ( ! $version ) {
			return; // Fresh install, no migrations needed
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
		}
	}

	/**
	 * Migrate data from version 5 to version 6.
	 *
	 * @param array $originData The original data from version 5.
	 *
	 * @return array The migrated data for version 6.
	 */
	public function migrateFromV5ToV6( array $originData ): array {

		$migratedData = [];

		// Specific migration logic from version 5 to version 6
		$keysToMigrate = [
			// Keys that remain the same
			'debug'                      => $originData['debug'] ?? 'no',
			'enabled'                    => $originData['enabled'] ?? 'no',
			'live_api_key'               => $originData['live_api_key'] ?? null,
			'test_api_key'               => $originData['test_api_key'] ?? null,
			'environment'                => $originData['environment'] ?? 'test',

			// Keys that have changed
			'excluded_products_message'  => $originData['cart_not_eligible_message_gift_cards'] ?? null,
			'pnx_description_field'      => $originData['description_alma'] ?? null,
			'paylater_description_field' => $originData['description_alma_pay_later'] ?? null,
			'paynow_description_field'   => $originData['description_alma_pay_now'] ?? null,
			'credit_description_field'   => $originData['description_alma_pnx_plus_4'] ?? null,
			'widget_cart_enabled'        => $originData['display_cart_eligibility'] ?? 'yes',
			'in_page_enabled'            => $originData['display_in_page'] ?? 'yes',
			'widget_product_enabled'     => $originData['display_product_eligibility'] ?? 'yes',
			'general_6_0_0_enabled'      => ( array_key_exists( 'enabled_general_6_0_0',
					$originData ) && $originData['enabled_general_6_0_0'] === 'yes' ) ? 1 : null,
			'general_10_0_0_enabled'     => ( array_key_exists( 'enabled_general_10_0_0',
					$originData ) && $originData['enabled_general_10_0_0'] === 'yes' ) ? 1 : null,
			'general_12_0_0_enabled'     => ( array_key_exists( 'enabled_general_12_0_0',
					$originData ) && $originData['enabled_general_12_0_0'] === 'yes' ) ? 1 : null,
			'general_24_0_0_enabled'     => ( array_key_exists( 'enabled_general_24_0_0',
					$originData ) && $originData['enabled_general_24_0_0'] === 'yes' ) ? 1 : null,
			'general_1_0_0_enabled'      => ( array_key_exists( 'enabled_general_1_0_0',
					$originData ) && $originData['enabled_general_1_0_0'] === 'yes' ) ? 1 : null,
			'general_1_15_0_enabled'     => ( array_key_exists( 'enabled_general_1_15_0',
					$originData ) && $originData['enabled_general_1_15_0'] === 'yes' ) ? 1 : null,
			'general_1_30_0_enabled'     => ( array_key_exists( 'enabled_general_1_30_0',
					$originData ) && $originData['enabled_general_1_30_0'] === 'yes' ) ? 1 : null,
			'general_1_45_0_enabled'     => ( array_key_exists( 'enabled_general_1_45_0',
					$originData ) && $originData['enabled_general_1_45_0'] === 'yes' ) ? 1 : null,
			'general_2_0_0_enabled'      => ( array_key_exists( 'enabled_general_2_0_0',
					$originData ) && $originData['enabled_general_2_0_0'] === 'yes' ) ? 1 : null,
			'general_3_0_0_enabled'      => ( array_key_exists( 'enabled_general_3_0_0',
					$originData ) && $originData['enabled_general_3_0_0'] === 'yes' ) ? 1 : null,
			'general_4_0_0_enabled'      => ( array_key_exists( 'enabled_general_4_0_0',
					$originData ) && $originData['enabled_general_4_0_0'] === 'yes' ) ? 1 : null,
			'excluded_products_list'     => $originData['excluded_products_list'] ?? null,
			'merchant_id'                => $originData['live_merchant_id'] ?? $originData['test_merchant_id'] ?? null,
			'general_6_0_0_max_amount'   => $originData['max_amount_general_6_0_0'] ?? null,
			'general_10_0_0_max_amount'  => $originData['max_amount_general_10_0_0'] ?? null,
			'general_12_0_0_max_amount'  => $originData['max_amount_general_12_0_0'] ?? null,
			'general_24_0_0_max_amount'  => $originData['max_amount_general_24_0_0'] ?? null,
			'general_1_0_0_max_amount'   => $originData['max_amount_general_1_0_0'] ?? null,
			'general_1_15_0_max_amount'  => $originData['max_amount_general_1_15_0'] ?? null,
			'general_1_30_0_max_amount'  => $originData['max_amount_general_1_30_0'] ?? null,
			'general_1_45_0_max_amount'  => $originData['max_amount_general_1_45_0'] ?? null,
			'general_2_0_0_max_amount'   => $originData['max_amount_general_2_0_0'] ?? null,
			'general_3_0_0_max_amount'   => $originData['max_amount_general_3_0_0'] ?? null,
			'general_4_0_0_max_amount'   => $originData['max_amount_general_4_0_0'] ?? null,
			'general_6_0_0_min_amount'   => $originData['min_amount_general_6_0_0'] ?? null,
			'general_10_0_0_min_amount'  => $originData['min_amount_general_10_0_0'] ?? null,
			'general_12_0_0_min_amount'  => $originData['min_amount_general_12_0_0'] ?? null,
			'general_24_0_0_min_amount'  => $originData['min_amount_general_24_0_0'] ?? null,
			'general_1_0_0_min_amount'   => $originData['min_amount_general_1_0_0'] ?? null,
			'general_1_15_0_min_amount'  => $originData['min_amount_general_1_15_0'] ?? null,
			'general_1_30_0_min_amount'  => $originData['min_amount_general_1_30_0'] ?? null,
			'general_1_45_0_min_amount'  => $originData['min_amount_general_1_45_0'] ?? null,
			'general_2_0_0_min_amount'   => $originData['min_amount_general_2_0_0'] ?? null,
			'general_3_0_0_min_amount'   => $originData['min_amount_general_3_0_0'] ?? null,
			'general_4_0_0_min_amount'   => $originData['min_amount_general_4_0_0'] ?? null,
			'pnx_title_field'            => $originData['title_alma'] ?? null,
			'paylater_title_field'       => $originData['title_alma_pay_later'] ?? null,
			'paynow_title_field'         => $originData['title_alma_pay_now'] ?? null,
			'credit_title_field'         => $originData['title_alma_pnx_plus_4'] ?? null,
		];

		foreach ( $keysToMigrate as $newKey => $migratedValue ) {
			// Try each old key in order until we find a non-null value
			if ( null !== $migratedValue ) {
				$migratedData[ $newKey ] = $migratedValue;
			}
		}

		// Update specific data formats if needed
		if ( array_key_exists( 'excluded_products_list', $migratedData )
		     && is_array( $migratedData['excluded_products_list'] )
		     && count( $migratedData['excluded_products_list'] ) > 0 ) {

			$newExcludedProductsList = [];
			// Ensure the excluded products list is stored as term IDs
			foreach ( $migratedData['excluded_products_list'] as $value ) {

				// Try to find the term by slug
				$term = get_term_by( 'slug', $value, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$newExcludedProductsList[] = (string) $term->term_id;
				}
			}

			$migratedData['excluded_products_list'] = $newExcludedProductsList;
		}

		return $migratedData;
	}
}
