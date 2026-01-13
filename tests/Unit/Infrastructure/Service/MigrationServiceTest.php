<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Service;

use Alma\Gateway\Infrastructure\Service\MigrationService;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use stdClass;

class MigrationServiceTest extends TestCase {

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
					'description_alma_in_page'                   => 'Fast and secure payment by credit card',
					'description_alma_in_page_pay_later'         => 'Fast and secure payment by credit card',
					'description_alma_in_page_pay_now'           => 'Fast and secure payment by credit card',
					'description_alma_in_page_pnx_plus_4'        => 'Fast and secure payment by credit card',
					'description_blocks_alma'                    => 'Fast and secure payment by credit card',
					'description_blocks_alma_in_page'            => 'Fast and secure payment by credit card',
					'description_blocks_alma_in_page_pay_later'  => 'Fast and secure payment by credit card',
					'description_blocks_alma_in_page_pay_now'    => 'Fast and secure payment by credit card',
					'description_blocks_alma_in_page_pnx_plus_4' => 'Fast and secure payment by credit card',
					'description_blocks_alma_pay_later'          => 'Fast and secure payment by credit card',
					'description_blocks_alma_pay_now'            => 'Fast and secure payment by credit card',
					'description_blocks_alma_pnx_plus_4'         => 'Fast and secure payment by credit card',
					'installments_count_general_4_0_0'           => 4,
					'payment_upon_trigger_display_text'          => 'at_shipping',
					'payment_upon_trigger_enabled'               => 'no',
					'payment_upon_trigger_event'                 => 'completed',
					'remove_order_on_close_in_page'              => 'yes',
					'selected_fee_plan'                          => 'general_3_0_0',
					'share_of_checkout_enabled'                  => 'no',
					'share_of_checkout_enabled_date'             => '2023-01-01 00:00:00',
					'test_merchant_name'                         => 'Test Merchant',
					'title_alma_in_page'                         => 'Pay in installments',
					'title_alma_in_page_pay_later'               => 'Pay later',
					'title_alma_in_page_pay_now'                 => 'Pay by credit card',
					'title_alma_in_page_pnx_plus_4'              => 'Pay with financing',
					'title_blocks_alma'                          => 'Pay in installments',
					'title_blocks_alma_in_page'                  => 'Pay in installments',
					'title_blocks_alma_in_page_pay_later'        => 'Pay later',
					'title_blocks_alma_in_page_pay_now'          => 'Pay by credit card',
					'title_blocks_alma_in_page_pnx_plus_4'       => 'Pay with financing',
					'title_blocks_alma_pay_later'                => 'Pay later',
					'title_blocks_alma_pay_now'                  => 'Pay by credit card',
					'title_blocks_alma_pnx_plus_4'               => 'Pay with financing',
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
					'description_alma'                     => 'Some description',
					'description_alma_pay_later'           => 'A second description',
					'description_alma_pay_now'             => 'A third description',
					'description_alma_pnx_plus_4'          => 'A fourth description',
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
					'title_alma'                           => 'A title',
					'title_alma_pay_later'                 => 'A second title',
					'title_alma_pay_now'                   => 'A third title',
					'title_alma_pnx_plus_4'                => 'A fourth title',
				],
				[
					'debug'                      => 'no',
					'enabled'                    => 'no',
					'environment'                => 'test',
					'excluded_products_message'  => 'A cart not eligible message',
					'pnx_description_field'      => 'Some description',
					'paylater_description_field' => 'A second description',
					'paynow_description_field'   => 'A third description',
					'credit_description_field'   => 'A fourth description',
					'widget_cart_enabled'        => 'yes',
					'in_page_enabled'            => 'yes',
					'widget_product_enabled'     => 'yes',
					'general_10_0_0_enabled'     => 1,
					'general_1_0_0_enabled'      => 1,
					'general_1_15_0_enabled'     => 1,
					'general_2_0_0_enabled'      => 1,
					'general_3_0_0_enabled'      => 1,
					'excluded_products_list'     => [ 0 => 19 ],
					'merchant_id'                => 'merchant_123',
					'general_10_0_0_max_amount'  => 21000,
					'general_12_0_0_max_amount'  => 21200,
					'general_1_0_0_max_amount'   => 20100,
					'general_1_15_0_max_amount'  => 21150,
					'general_1_30_0_max_amount'  => 21300,
					'general_2_0_0_max_amount'   => 20200,
					'general_3_0_0_max_amount'   => 20300,
					'general_4_0_0_max_amount'   => 20400,
					'general_10_0_0_min_amount'  => 11000,
					'general_12_0_0_min_amount'  => 11200,
					'general_1_0_0_min_amount'   => 10100,
					'general_1_15_0_min_amount'  => 11150,
					'general_1_30_0_min_amount'  => 11300,
					'general_2_0_0_min_amount'   => 10200,
					'general_3_0_0_min_amount'   => 10300,
					'general_4_0_0_min_amount'   => 10400,
					'pnx_title_field'            => 'A title',
					'paylater_title_field'       => 'A second title',
					'paynow_title_field'         => 'A third title',
					'credit_title_field'         => 'A fourth title',
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
		];
	}

	public function setUp(): void {
		Monkey\setUp();

		// Mock les hooks WordPress pour éviter l'erreur
		$term          = new stdClass();
		$term->term_id = 19;
		$term->name    = 'Music';
		$term->slug    = 'music';
		Functions\when( 'get_term_by' )->justReturn( $term );
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider migrationDataProvider
	 */
	public function testMigrateFromV5ToV6( $originData, $expectedData ): void {

		$migrationService = new MigrationService();
		$migratedData     = $migrationService->migrateFromV5ToV6( $originData );

		$this->assertEquals( $migratedData, $expectedData );
	}
}
