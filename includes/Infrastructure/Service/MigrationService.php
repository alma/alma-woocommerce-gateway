<?php

namespace Alma\Gateway\Infrastructure\Service;

class MigrationService {

	const VERSION_KEY = 'alma_version';
	const OLD_KEY = 'wc_alma_settings';
	const NEW_KEY = 'woocommerce_alma_config_gateway_settings';
	const MIGRATION_LOCK = 'alma_migration_lock';

	const VERSION_6_0_0 = '6.0.0';

	public function runMigrationsIfNeeded() {

		// Check if a version number exists in the database
		$version = get_option( self::VERSION_KEY, null );
		if ( ! $version ) {
			return; // Fresh install, no migrations needed
		}

		// Compare to the current plugin version. If different, run necessary migrations
		if ( version_compare( $version, self::VERSION_6_0_0, '<' ) ) {

			try {
				// Add a migration lock to prevent concurrent migrations
				add_option( self::MIGRATION_LOCK, time(), '', false );

				// Migration from version 5 to version 6
				$migratedData = $this->migrateFromV5ToV6( get_option( self::OLD_KEY ) );
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
			'debug'                                => 'debug',
			'enabled'                              => 'enabled',
			'live_api_key'                         => 'live_api_key',
			'test_api_key'                         => 'test_api_key',

			// Keys that have changed
			'cart_not_eligible_message_gift_cards' => 'excluded_products_message',
			'description_alma'                     => 'pnx_description_field',
			'description_alma_pay_later'           => 'paylater_description_field',
			'description_alma_pay_now'             => 'paynow_description_field',
			'description_alma_pnx_plus_4'          => 'credit_description_field',
			'display_cart_eligibility'             => 'widget_cart_enabled',
			'display_in_page'                      => 'in_page_enabled',
			'display_product_eligibility'          => 'widget_product_enabled',
			'enabled_general_10_0_0'               => 'general_10_0_0_enabled',
			'enabled_general_12_0_0'               => 'general_12_0_0_enabled',
			'enabled_general_1_0_0'                => 'general_1_0_0_enabled',
			'enabled_general_1_15_0'               => 'general_1_15_0_enabled',
			'enabled_general_1_30_0'               => 'general_1_30_0_enabled',
			'enabled_general_2_0_0'                => 'general_2_0_0_enabled',
			'enabled_general_3_0_0'                => 'general_3_0_0_enabled',
			'excluded_products_list'               => 'excluded_products_list',
			'live_merchant_id'                     => 'merchant_id',
			'max_amount_general_10_0_0'            => 'general_10_0_0_max_amount',
			'max_amount_general_12_0_0'            => 'general_12_0_0_max_amount',
			'max_amount_general_1_0_0'             => 'general_1_0_0_max_amount',
			'max_amount_general_1_15_0'            => 'general_1_15_0_max_amount',
			'max_amount_general_1_30_0'            => 'general_1_30_0_max_amount',
			'max_amount_general_2_0_0'             => 'general_2_0_0_max_amount',
			'max_amount_general_3_0_0'             => 'general_3_0_0_max_amount',
			'max_amount_general_4_0_0'             => 'general_4_0_0_max_amount',
			'min_amount_general_10_0_0'            => 'general_10_0_0_min_amount',
			'min_amount_general_12_0_0'            => 'general_12_0_0_min_amount',
			'min_amount_general_1_0_0'             => 'general_1_0_0_min_amount',
			'min_amount_general_1_15_0'            => 'general_1_15_0_min_amount',
			'min_amount_general_1_30_0'            => 'general_1_30_0_min_amount',
			'min_amount_general_2_0_0'             => 'general_2_0_0_min_amount',
			'min_amount_general_3_0_0'             => 'general_3_0_0_min_amount',
			'min_amount_general_4_0_0'             => 'general_4_0_0_min_amount',
			'title_alma'                           => 'pnx_title_field',
			'title_alma_pay_later'                 => 'paylater_title_field',
			'title_alma_pay_now'                   => 'paynow_title_field',
			'title_alma_pnx_plus_4'                => 'credit_title_field',
		];

		foreach ( $keysToMigrate as $oldKey => $newKey ) {
			if ( array_key_exists( $oldKey, $originData ) ) {
				$migratedData[ $newKey ] = $originData[ $oldKey ];
			}
		}

		return $migratedData;
	}
}
