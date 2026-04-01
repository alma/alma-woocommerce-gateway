<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Service;

use Alma\Gateway\Infrastructure\Service\MigrationService;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

class MigrationServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}


	public static function migrationDataProvider(): array {
		return [
			'empty keys'                            => [
				[],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
				]
			],
			'deprecated keys'                       => [
				[
					'allowed_fee_plans',
					'deferred_days_general_10_0_0'               => 0,
					'deferred_days_general_12_0_0'               => 0,
					'deferred_days_general_1_0_0'                => 0,
					'deferred_days_general_1_15_0'               => 15,
					'deferred_days_general_1_30_0'               => 30,
					'deferred_days_general_2_0_0'                => 0,
					'deferred_days_general_3_0_0'                => 0,
					'deferred_days_general_4_0_0'                => 0,
					'deferred_months_general_10_0_0'             => 0,
					'deferred_months_general_12_0_0'             => 0,
					'deferred_months_general_1_0_0'              => 0,
					'deferred_months_general_1_15_0'             => 0,
					'deferred_months_general_1_30_0'             => 0,
					'deferred_months_general_2_0_0'              => 0,
					'deferred_months_general_3_0_0'              => 0,
					'deferred_months_general_4_0_0'              => 0,
					'installments_count_general_4_0_0'           => 4,
					'payment_upon_trigger_display_text'          => 'at_shipping',
					'payment_upon_trigger_enabled'               => 'no',
					'payment_upon_trigger_event'                 => 'completed',
					'remove_order_on_close_in_page'              => 'yes',
					'selected_fee_plan'                          => 'general_3_0_0',
					'share_of_checkout_enabled'                  => 'no',
					'share_of_checkout_enabled_date'             => '2023-01-01 00:00:00',
					'test_merchant_name'                         => 'Test Merchant',
					'variable_product_check_variations_event'    => 'show_variation',
					'variable_product_price_query_selector'      => 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount',
					'variable_product_sale_price_query_selector' => 'form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount',
					'woocommerce_alma_share_of_checkout_enabled' => 'no',
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
				]
			],
			'good keys'                             => [
				[
					'debug'        => 'no',
					'enabled'      => 'yes',
					'live_api_key' => 'encrypted_live_key',
					'test_api_key' => 'encrypted_test_key',
					'environment'  => 'test',
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'yes',
					'live_api_key'           => 'encrypted_live_key',
					'test_api_key'           => 'encrypted_test_key',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
				]
			],
			'changed keys'                          => [
				[
					'cart_not_eligible_message_gift_cards' => 'A cart not eligible message',
					'display_cart_eligibility'             => 'yes',
					'display_in_page'                      => 'yes',
					'display_product_eligibility'          => 'yes',
					'enabled_general_10_0_0'               => 'yes',
					'enabled_general_12_0_0'               => 'no',
					'enabled_general_1_0_0'                => 'yes',
					'enabled_general_1_15_0'               => 'yes',
					'enabled_general_1_30_0'               => 'no',
					'enabled_general_2_0_0'                => 'yes',
					'enabled_general_3_0_0'                => 'yes',
					'excluded_products_list'               => [ 0 => "music" ],
					'live_merchant_id'                     => 'merchant_123',
					'max_amount_general_10_0_0'            => 21000,
					'max_amount_general_12_0_0'            => 21200,
					'max_amount_general_1_0_0'             => 20100,
					'max_amount_general_1_15_0'            => 21150,
					'max_amount_general_1_30_0'            => 21300,
					'max_amount_general_2_0_0'             => 20200,
					'max_amount_general_3_0_0'             => 20300,
					'max_amount_general_4_0_0'             => 20400,
					'min_amount_general_10_0_0'            => 11000,
					'min_amount_general_12_0_0'            => 11200,
					'min_amount_general_1_0_0'             => 10100,
					'min_amount_general_1_15_0'            => 11150,
					'min_amount_general_1_30_0'            => 11300,
					'min_amount_general_2_0_0'             => 10200,
					'min_amount_general_3_0_0'             => 10300,
					'min_amount_general_4_0_0'             => 10400,
				],
				[
					'debug'                     => 'no',
					'enabled'                   => 'no',
					'environment'               => 'test',
					'excluded_products_message' => 'A cart not eligible message',
					'widget_cart_enabled'       => 'yes',
					'in_page_enabled'           => 'yes',
					'widget_product_enabled'    => 'yes',
					'general_10_0_0_enabled'    => 1,
					'general_1_0_0_enabled'     => 1,
					'general_1_15_0_enabled'    => 1,
					'general_2_0_0_enabled'     => 1,
					'general_3_0_0_enabled'     => 1,
					'excluded_products_list'    => [ 0 => "music" ],
					'merchant_id'               => 'merchant_123',
					'general_10_0_0_max_amount' => 21000,
					'general_12_0_0_max_amount' => 21200,
					'general_1_0_0_max_amount'  => 20100,
					'general_1_15_0_max_amount' => 21150,
					'general_1_30_0_max_amount' => 21300,
					'general_2_0_0_max_amount'  => 20200,
					'general_3_0_0_max_amount'  => 20300,
					'general_4_0_0_max_amount'  => 20400,
					'general_10_0_0_min_amount' => 11000,
					'general_12_0_0_min_amount' => 11200,
					'general_1_0_0_min_amount'  => 10100,
					'general_1_15_0_min_amount' => 11150,
					'general_1_30_0_min_amount' => 11300,
					'general_2_0_0_min_amount'  => 10200,
					'general_3_0_0_min_amount'  => 10300,
					'general_4_0_0_min_amount'  => 10400,
				],
			],
			'another changed keys'                  => [
				[
					'display_cart_eligibility'    => 'no',
					'display_in_page'             => 'no',
					'display_product_eligibility' => 'no',
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'no',
					'in_page_enabled'        => 'no',
					'widget_product_enabled' => 'no',
				],
			],
			'merchant keys - only live key defined' => [
				[
					'live_merchant_id' => 'merchant_123',
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
					'merchant_id'            => 'merchant_123'
				]
			],
			'merchant keys - only test key defined' => [
				[
					'test_merchant_id' => 'merchant_123',
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
					'merchant_id'            => 'merchant_123'
				]
			],
			'merchant keys - two keys defined'      => [
				[
					'test_merchant_id' => 'merchant_456',
					'live_merchant_id' => 'merchant_123',
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
					'merchant_id'            => 'merchant_123'
				]
			],
			'merchant keys - no keys defined'       => [
				[
				],
				[
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
				]
			],
			'descriptions'                          => [
				[
					'display_in_page'                => 'no',
					'description_block_alma_in_page' => 'A description for in page blocks',
					'description_blocks_alma'        => 'A description for blocks',
					'description_alma_in_page'       => 'A description for in page',
					'description_alma'               => 'A description',

					'description_blocks_alma_in_page_pay_later' => 'A second description for in page blocks',
					'description_blocks_alma_pay_later'         => 'A second description for blocks',
					'description_alma_in_page_pay_later'        => 'A second description for in page',
					'description_alma_pay_later'                => 'A second description',

					'description_blocks_alma_in_page_pay_now' => 'A third description for in page blocks',
					'description_blocks_alma_pay_now'         => 'A third description for blocks',
					'description_alma_in_page_pay_now'        => 'A third description for in page',
					'description_alma_pay_now'                => 'A third description',

					'description_blocks_alma_in_page_pnx_plus_4' => 'A fourth description for in page blocks',
					'description_blocks_alma_pnx_plus_4'         => 'A fourth description for blocks',
					'description_alma_in_page_pnx_plus_4'        => 'A fourth description for in page',
					'description_alma_pnx_plus_4'                => 'A fourth description',
				],
				[
					'pnx_description_field'      => 'A description for blocks',
					'paylater_description_field' => 'A second description for blocks',
					'paynow_description_field'   => 'A third description for blocks',
					'credit_description_field'   => 'A fourth description for blocks',
					'debug'                      => 'no',
					'enabled'                    => 'no',
					'environment'                => 'test',
					'widget_cart_enabled'        => 'yes',
					'in_page_enabled'            => 'no',
					'widget_product_enabled'     => 'yes',
				]
			],
			'titles'                                => [
				[
					'display_in_page'           => 'yes',
					'title_blocks_alma_in_page' => 'A title for in page blocks',
					'title_blocks_alma'         => 'A title for blocks',
					'title_alma_in_page'        => 'A title for in page',
					'title_alma'                => 'A title',

					'title_blocks_alma_in_page_pay_later' => 'A second title for in page blocks',
					'title_blocks_alma_pay_later'         => 'A second title for blocks',
					'title_alma_in_page_pay_later'        => 'A second title for in page',
					'title_alma_pay_later'                => 'A second title',

					'title_blocks_alma_in_page_pay_now' => 'A third title for in page blocks',
					'title_blocks_alma_pay_now'         => 'A third title for blocks',
					'title_alma_in_page_pay_now'        => 'A third title for in page',
					'title_alma_pay_now'                => 'A third title',

					'title_blocks_alma_in_page_pnx_plus_4' => 'A fourth title for in page blocks',
					'title_blocks_alma_pnx_plus_4'         => 'A fourth title for blocks',
					'title_alma_in_page_pnx_plus_4'        => 'A fourth title for in page',
					'title_alma_pnx_plus_4'                => 'A fourth title',
				],
				[
					'pnx_title_field'        => 'A title for in page blocks',
					'paylater_title_field'   => 'A second title for in page blocks',
					'paynow_title_field'     => 'A third title for in page blocks',
					'credit_title_field'     => 'A fourth title for in page blocks',
					'debug'                  => 'no',
					'enabled'                => 'no',
					'environment'            => 'test',
					'widget_cart_enabled'    => 'yes',
					'in_page_enabled'        => 'yes',
					'widget_product_enabled' => 'yes',
				]
			],
		];
	}

	/**
	 * @dataProvider migrationDataProvider
	 */
	public function testMigrateFromV5ToV6( $originData, $expectedData ): void {

		$migrationService = new MigrationService();
		$migratedData     = $migrationService->migrateFromV5ToV6( $originData );

		// Check that all expected keys are present with the correct values
		foreach ( $expectedData as $key => $value ) {
			$this->assertArrayHasKey( $key, $migratedData, "Missing key: $key" );
			$this->assertEquals( $value, $migratedData[ $key ], "Wrong value for key: $key" );
		}

		// Check that no unexpected non-amount keys are present
		foreach ( $migratedData as $key => $value ) {
			if ( preg_match( '/_min_amount$|_max_amount$/', $key ) ) {
				continue; // Amount keys are tested separately in testMigrateAmountLimitsDefaultToZero
			}
			$this->assertArrayHasKey( $key, $expectedData, "Unexpected key in migrated data: $key" );
		}
	}

	/**
	 * Test that amount limits default to 0 instead of null when not present in v5 settings.
	 * This prevents incomplete fee plan groups that would crash FeePlanConfiguration::__construct().
	 */
	public function testMigrateAmountLimitsDefaultToZero(): void {
		$migrationService = new MigrationService();

		// v5 settings with Pay Now enabled but NO min/max amounts customized
		$v5Settings   = [
			'enabled_general_1_0_0' => 'yes',
		];
		$migratedData = $migrationService->migrateFromV5ToV6( $v5Settings );

		// All plans should have min/max amount entries with 0 as default
		$plans = [ '6_0_0', '10_0_0', '12_0_0', '24_0_0', '1_0_0', '1_15_0', '1_30_0', '1_45_0', '2_0_0', '3_0_0', '4_0_0' ];
		foreach ( $plans as $plan ) {
			$this->assertArrayHasKey( "general_{$plan}_min_amount", $migratedData );
			$this->assertArrayHasKey( "general_{$plan}_max_amount", $migratedData );
			$this->assertSame( 0, $migratedData["general_{$plan}_min_amount"],
				"Plan {$plan} min_amount should default to 0" );
			$this->assertSame( 0, $migratedData["general_{$plan}_max_amount"],
				"Plan {$plan} max_amount should default to 0" );
		}
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testRunMigrationsFrom600CleansUpSocAndBumpsVersion(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'alma_migration_lock', null )
			->andReturn( false );
		Functions\expect( 'get_option' )
			->once()
			->with( 'alma_version', null )
			->andReturn( '6.0.0' );

		Functions\expect( 'delete_option' )
			->once()
			->with( 'alma_soc_ongoing' );

		Functions\expect( 'update_option' )
			->once()
			->with( 'alma_version', '6.0.7' );

		$migrationService = new MigrationService();
		$result           = $migrationService->runMigrationsIfNeeded();

		$this->assertTrue( $result );
	}

	/**
	 * Test that existing v5 amount limits are properly migrated (not overwritten by default).
	 */
	public function testMigrateAmountLimitsPreservesExistingValues(): void {
		$migrationService = new MigrationService();

		$v5Settings   = [
			'min_amount_general_1_0_0' => 5000,
			'max_amount_general_1_0_0' => 200000,
		];
		$migratedData = $migrationService->migrateFromV5ToV6( $v5Settings );

		$this->assertSame( 5000, $migratedData['general_1_0_0_min_amount'] );
		$this->assertSame( 200000, $migratedData['general_1_0_0_max_amount'] );

		// Other plans should still get 0
		$this->assertSame( 0, $migratedData['general_3_0_0_min_amount'] );
		$this->assertSame( 0, $migratedData['general_3_0_0_max_amount'] );
	}
}
