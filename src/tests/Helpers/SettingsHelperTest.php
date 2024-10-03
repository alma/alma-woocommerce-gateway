<?php
/**
 * Class SettingsHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\CartHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\SettingsHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\SettingsHelper
 */
class SettingsHelperTest extends WP_UnitTestCase {

	protected $result_old_default_settings  =  array(
		'enabled'                                    => 'yes',
		'payment_upon_trigger_enabled'               => 'no',
		'payment_upon_trigger_event'                 => 'completed',
		'payment_upon_trigger_display_text'          => 'at_shipping',
		'selected_fee_plan'                          => 'general_3_0_0',
		'enabled_general_3_0_0'                      => 'yes',
		'title_alma_in_page'                         => 'Pay in installments',
		'description_alma_in_page'                   => 'Fast and secure payment by credit card',
		'title_alma_in_page_pay_now'                 => 'Pay by credit card',
		'description_alma_in_page_pay_now'           => 'Fast and secured payments',
		'title_alma_in_page_pay_later'               => 'Pay later',
		'description_alma_in_page_pay_later'         => 'Fast and secure payment by credit card',
		'title_alma'                                 => 'Pay in installments',
		'description_alma'                           => 'Fast and secure payment by credit card',
		'title_alma_pay_now'                         => 'Pay by credit card',
		'description_alma_pay_now'                   => 'Fast and secured payments',
		'title_alma_pay_later'                       => 'Pay later',
		'description_alma_pay_later'                 => 'Fast and secure payment by credit card',
		'title_alma_pnx_plus_4'                      =>'Pay with financing',
		'description_alma_pnx_plus_4'                => 'Fast and secure payment by credit card',
		'title_blocks_alma_in_page'                  => 'Pay in installments',
		'description_blocks_alma_in_page'            => 'Fast and secure payment by credit card',
		'title_blocks_alma_in_page_pay_now'          => 'Pay by credit card',
		'description_blocks_alma_in_page_pay_now'    => 'Fast and secured payments',
		'title_blocks_alma_in_page_pay_later'        => 'Pay later',
		'description_blocks_alma_in_page_pay_later'  => 'Fast and secure payment by credit card',
		'title_blocks_alma'                          => 'Pay in installments',
		'description_blocks_alma'                    => 'Fast and secure payment by credit card',
		'title_blocks_alma_pay_now'                  => 'Pay by credit card',
		'description_blocks_alma_pay_now'            => 'Fast and secured payments',
		'title_blocks_alma_pay_later'                => 'Pay later',
		'description_blocks_alma_pay_later'          => 'Fast and secure payment by credit card',
		'title_blocks_alma_pnx_plus_4'               =>'Pay with financing',
		'description_blocks_alma_pnx_plus_4'         => 'Fast and secure payment by credit card',
		'display_cart_eligibility'                   => 'yes',
		'display_product_eligibility'                => 'yes',
		'variable_product_price_query_selector'      => 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount',
		'variable_product_sale_price_query_selector' => 'form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount',
		'variable_product_check_variations_event'    => 'check_variations',
		'excluded_products_list'                     => array(),
		'cart_not_eligible_message_gift_cards'       => 'Some products cannot be paid with monthly or deferred installments',
		'live_api_key'                               => '',
		'test_api_key'                               => '',
		'environment'                                => 'test',
		'share_of_checkout_enabled'                  => 'no',
		'debug'                                      => 'yes',
		'keys_validity'                              => 'no',
		'display_in_page'                            => 'no',
		'use_blocks_template'                        => 'no',
		'title_alma_in_page_pnx_plus_4' 			 => 'Pay with financing',
		'description_alma_in_page_pnx_plus_4' 		 => 'Fast and secure payment by credit card'
	);

	protected $alma_settings =  '{"enabled":"yes","payment_upon_trigger_enabled":"no","payment_upon_trigger_event":"completed","payment_upon_trigger_display_text":"at_shipping","selected_fee_plan":"general_12_0_0","enabled_general_3_0_0":"yes","title_alma_in_page":"Pay in installments","description_alma_in_page":"Fast and secure payment by credit card","title_alma_in_page_pay_now":"Pay by credit card","description_alma_in_page_pay_now":"Fast and secured payments","title_alma_in_page_pay_later":"Pay later","description_alma_in_page_pay_later":"Fast and secure payment by credit card","title_alma":"Pay in installments","description_alma":"Fast and secure payment by credit card","title_alma_pay_now":"Pay by credit card","description_alma_pay_now":"Fast and secured payments","title_alma_pay_later":"Pay later","description_alma_pay_later":"Fast and secure payment by credit card","title_alma_pnx_plus_4":"Pay with financing","description_alma_pnx_plus_4":"Fast and secure payment by credit card","title_blocks_alma_in_page":"Pay in installments","description_blocks_alma_in_page":"Fast and secure payment by credit card","title_blocks_alma_in_page_pay_now":"Pay by credit card","description_blocks_alma_in_page_pay_now":"Fast and secured payments","title_blocks_alma_in_page_pay_later":"Pay later","description_blocks_alma_in_page_pay_later":"Fast and secure payment by credit card","title_blocks_alma":"Pay in installments","description_blocks_alma":"Fast and secure payment by credit card","title_blocks_alma_pay_now":"Pay by credit card","description_blocks_alma_pay_now":"Fast and secured payments","title_blocks_alma_pay_later":"Pay later","description_blocks_alma_pay_later":"Fast and secure payment by credit card","title_blocks_alma_pnx_plus_4":"Pay with financing","description_blocks_alma_pnx_plus_4":"Fast and secure payment by credit card","display_cart_eligibility":"yes","display_product_eligibility":"yes","variable_product_price_query_selector":"form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi","variable_product_sale_price_query_selector":"form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount bdi","variable_product_check_variations_event":"check_variations","excluded_products_list":"","cart_not_eligible_message_gift_cards":"Some products cannot be paid with monthly or deferred installments","live_api_key":"","test_api_key":"123","environment":"test","share_of_checkout_enabled":"no","debug":"yes","keys_validity":"yes","display_in_page":"yes","use_blocks_template":"yes","allowed_fee_plans":[{"installments_count":1,"kind":"general","deferred_months":0,"deferred_days":30,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":500,"merchant_fee_fixed":0,"customer_fee_variable":0,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false},{"installments_count":1,"kind":"general","deferred_months":0,"deferred_days":0,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":50,"allowed":true,"merchant_fee_variable":75,"merchant_fee_fixed":0,"customer_fee_variable":0,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"mon_marchand","payout_on_acceptance":false},{"installments_count":1,"kind":"general","deferred_months":0,"deferred_days":15,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":440,"merchant_fee_fixed":0,"customer_fee_variable":0,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false},{"installments_count":2,"kind":"general","deferred_months":0,"deferred_days":0,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":340,"merchant_fee_fixed":0,"customer_fee_variable":0,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false},{"installments_count":3,"kind":"general","deferred_months":0,"deferred_days":0,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":261,"merchant_fee_fixed":0,"customer_fee_variable":99,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false},{"installments_count":4,"kind":"general","deferred_months":0,"deferred_days":0,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":439,"merchant_fee_fixed":0,"customer_fee_variable":1,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false},{"installments_count":10,"kind":"general","deferred_months":0,"deferred_days":0,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":380,"merchant_fee_fixed":0,"customer_fee_variable":0,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false},{"installments_count":12,"kind":"general","deferred_months":0,"deferred_days":0,"deferred_trigger_limit_days":null,"max_purchase_amount":200000,"min_purchase_amount":5000,"allowed":true,"merchant_fee_variable":380,"merchant_fee_fixed":0,"customer_fee_variable":0,"customer_lending_rate":0,"customer_fee_fixed":0,"id":null,"available_in_pos":true,"capped":false,"deferred_trigger_bypass_scoring":false,"first_installment_ratio":null,"is_under_maximum_interest_regulated_rate":true,"merchant":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","payout_on_acceptance":false}],"live_merchant_id":null,"test_merchant_id":"merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw","test_merchant_name":"Claire","min_amount_general_1_30_0":5000,"max_amount_general_1_30_0":200000,"enabled_general_1_30_0":"yes","deferred_months_general_1_30_0":0,"deferred_days_general_1_30_0":30,"installments_count_general_1_30_0":1,"min_amount_general_1_0_0":100,"max_amount_general_1_0_0":200000,"enabled_general_1_0_0":"yes","deferred_months_general_1_0_0":0,"deferred_days_general_1_0_0":0,"installments_count_general_1_0_0":1,"min_amount_general_1_15_0":5000,"max_amount_general_1_15_0":200000,"enabled_general_1_15_0":"yes","deferred_months_general_1_15_0":0,"deferred_days_general_1_15_0":15,"installments_count_general_1_15_0":1,"min_amount_general_2_0_0":5000,"max_amount_general_2_0_0":200000,"enabled_general_2_0_0":"yes","deferred_months_general_2_0_0":0,"deferred_days_general_2_0_0":0,"installments_count_general_2_0_0":2,"min_amount_general_3_0_0":5000,"max_amount_general_3_0_0":200000,"deferred_months_general_3_0_0":0,"deferred_days_general_3_0_0":0,"installments_count_general_3_0_0":3,"min_amount_general_4_0_0":5000,"max_amount_general_4_0_0":200000,"enabled_general_4_0_0":"yes","deferred_months_general_4_0_0":0,"deferred_days_general_4_0_0":0,"installments_count_general_4_0_0":4,"min_amount_general_10_0_0":5000,"max_amount_general_10_0_0":200000,"enabled_general_10_0_0":"yes","deferred_months_general_10_0_0":0,"deferred_days_general_10_0_0":0,"installments_count_general_10_0_0":10,"min_amount_general_12_0_0":5000,"max_amount_general_12_0_0":200000,"enabled_general_12_0_0":"yes","deferred_months_general_12_0_0":0,"deferred_days_general_12_0_0":0,"installments_count_general_12_0_0":12}';

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_settings
	 *
	 * @return void
	 */
	public function test_default_settings() {
		// Without WPML with  version < 4.4.0
		$internalionalization_helper = \Mockery::mock(InternationalizationHelper::class);
		$internalionalization_helper->shouldReceive('is_site_multilingual')->andReturn(false);
		$version_factory = \Mockery::mock(VersionFactory::class);
		$version_factory->shouldReceive('get_version')->andReturn('2.0.0');

		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
			->andReturn($version_factory);
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);

		$settings_helper = $settings_helper_builder->get_instance();

		$result = $this->result_old_default_settings;
		$this->assertEquals($result, $settings_helper->default_settings());

		// Without WPML with  version > 4.4.0
		$internalionalization_helper = \Mockery::mock(InternationalizationHelper::class);
		$internalionalization_helper->shouldReceive('is_site_multilingual')->andReturn(false);
		$version_factory = \Mockery::mock(VersionFactory::class);
		$version_factory->shouldReceive('get_version')->andReturn('5.0.0');

		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);

		$settings_helper = $settings_helper_builder->get_instance();

		$result = $this->result_old_default_settings;
		$result['variable_product_price_query_selector']     = 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi';
		$result['variable_product_sale_price_query_selector'] = 'form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount bdi';
		$this->assertEquals($result, $settings_helper->default_settings());

		// With WPML with  version < 4.4.0
		$internalionalization_helper = \Mockery::mock(InternationalizationHelper::class);
		$internalionalization_helper->shouldReceive('is_site_multilingual')->andReturn(true);
		$version_factory = \Mockery::mock(VersionFactory::class);
		$version_factory->shouldReceive('get_version')->andReturn('2.0.0');

		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$result = $this->result_old_default_settings;

		$this->assertEquals($result, $settings_helper->default_settings());

		// Sith WPML with  version > 4.4.0
		$internalionalization_helper = \Mockery::mock(InternationalizationHelper::class);
		$internalionalization_helper->shouldReceive('is_site_multilingual')->andReturn(true);
		$version_factory = \Mockery::mock(VersionFactory::class);
		$version_factory->shouldReceive('get_version')->andReturn('5.0.0');

		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$result = $this->result_old_default_settings;
		$result['variable_product_price_query_selector']     = 'form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi';
		$result['variable_product_sale_price_query_selector'] = 'form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount bdi';
		$this->assertEquals($result, $settings_helper->default_settings());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_pnx_title
	 *
	 * @return void
	 */
	public function test_default_pnx_title() {
		$result = 'Pay in installments';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );

		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pnx_title());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pnx_title());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_pay_now_title
	 *
	 * @return void
	 */
	public function test_default_pay_now_title() {
		$result = 'Pay by credit card';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pay_now_title());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pay_now_title());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_description
	 *
	 * @return void
	 */
	public function test_default_description() {
		$result = 'Fast and secured payments';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_description());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_description());
	}


	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_payment_description
	 *
	 * @return void
	 */
	public function test_default_payment_description() {
		$result = 'Fast and secure payment by credit card';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_payment_description());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_payment_description());
	}


	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_pay_later_title
	 *
	 * @return void
	 */
	public function test_default_pay_later_title() {
		$result = 'Pay later';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pay_later_title());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pay_later_title());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_pnx_plus_4_title
	 *
	 * @return void
	 */
	public function test_default_pnx_plus_4_title() {
		$result = 'Pay with financing';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pnx_plus_4_title());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_pnx_plus_4_title());
	}


	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_variable_price_selector
	 *
	 * @return void
	 */
	public function test_default_variable_price_selector() {
		// Version < 4.4.0
		$version_factory = \Mockery::mock( VersionFactory::class );
		$version_factory->shouldReceive( 'get_version' )->andReturn( '2.0.0' );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals('form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount', $settings_helper->default_variable_price_selector());

		// Version >= 4.4.0
		$version_factory = \Mockery::mock( VersionFactory::class );
		$version_factory->shouldReceive( 'get_version' )->andReturn( '4.4.0' );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);
		$settings_helper = $settings_helper_builder->get_instance();
		
		$this->assertEquals('form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi', $settings_helper->default_variable_price_selector());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_variable_sale_price_selector
	 *
	 * @return void
	 */
	public function default_variable_sale_price_selector() {
		// Version < 4.4.0
		$version_factory = \Mockery::mock( VersionFactory::class );
		$version_factory->shouldReceive( 'get_version' )->andReturn( '2.0.0' );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals('form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount', $settings_helper->default_variable_sale_price_selector());

		// Version >= 4.4.0
		$version_factory = \Mockery::mock( VersionFactory::class );
		$version_factory->shouldReceive( 'get_version' )->andReturn( '4.4.0' );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_version_factory')
		                        ->andReturn($version_factory);

		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals('form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount bdi', $settings_helper->default_variable_sale_price_selector());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::default_not_eligible_cart_message
	 *
	 * @return void
	 */
	public function test_default_not_eligible_cart_message() {
		$result = 'Some products cannot be paid with monthly or deferred installments';

		// Without WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( false );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);

		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertEquals($result, $settings_helper->default_not_eligible_cart_message());

		// With WPML
		$internalionalization_helper = \Mockery::mock( InternationalizationHelper::class );
		$internalionalization_helper->shouldReceive( 'is_site_multilingual' )->andReturn( true );
		$settings_helper_builder = \Mockery::mock(SettingsHelperBuilder::class)->makePartial();
		$settings_helper_builder->shouldReceive('get_internalionalization_helper')
		                        ->andReturn($internalionalization_helper);

		$settings_helper = $settings_helper_builder->get_instance();


		$this->assertEquals($result, $settings_helper->default_not_eligible_cart_message());
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::reset_plans
	 *
	 * @return void
	 */
	public function test_reset_plans() {

		$settings_helper_builder = new SettingsHelperBuilder();
		$settings_helper = $settings_helper_builder->get_instance();

		$result = '{"enabled":"yes","payment_upon_trigger_enabled":"no","payment_upon_trigger_event":"completed","payment_upon_trigger_display_text":"at_shipping","selected_fee_plan":"general_12_0_0","enabled_general_3_0_0":"yes","title_alma_in_page":"Pay in installments","description_alma_in_page":"Fast and secure payment by credit card","title_alma_in_page_pay_now":"Pay by credit card","description_alma_in_page_pay_now":"Fast and secured payments","title_alma_in_page_pay_later":"Pay later","description_alma_in_page_pay_later":"Fast and secure payment by credit card","title_alma":"Pay in installments","description_alma":"Fast and secure payment by credit card","title_alma_pay_now":"Pay by credit card","description_alma_pay_now":"Fast and secured payments","title_alma_pay_later":"Pay later","description_alma_pay_later":"Fast and secure payment by credit card","title_alma_pnx_plus_4":"Pay with financing","description_alma_pnx_plus_4":"Fast and secure payment by credit card","title_blocks_alma_in_page":"Pay in installments","description_blocks_alma_in_page":"Fast and secure payment by credit card","title_blocks_alma_in_page_pay_now":"Pay by credit card","description_blocks_alma_in_page_pay_now":"Fast and secured payments","title_blocks_alma_in_page_pay_later":"Pay later","description_blocks_alma_in_page_pay_later":"Fast and secure payment by credit card","title_blocks_alma":"Pay in installments","description_blocks_alma":"Fast and secure payment by credit card","title_blocks_alma_pay_now":"Pay by credit card","description_blocks_alma_pay_now":"Fast and secured payments","title_blocks_alma_pay_later":"Pay later","description_blocks_alma_pay_later":"Fast and secure payment by credit card","title_blocks_alma_pnx_plus_4":"Pay with financing","description_blocks_alma_pnx_plus_4":"Fast and secure payment by credit card","display_cart_eligibility":"yes","display_product_eligibility":"yes","variable_product_price_query_selector":"form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi","variable_product_sale_price_query_selector":"form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount bdi","variable_product_check_variations_event":"check_variations","excluded_products_list":"","cart_not_eligible_message_gift_cards":"Some products cannot be paid with monthly or deferred installments","live_api_key":"","test_api_key":"123","environment":"test","share_of_checkout_enabled":"no","debug":"yes","keys_validity":"yes","display_in_page":"yes","use_blocks_template":"yes","allowed_fee_plans":null,"live_merchant_id":null,"test_merchant_id":null,"test_merchant_name":"Claire","min_amount_general_1_30_0":null,"max_amount_general_1_30_0":null,"enabled_general_1_30_0":"yes","deferred_months_general_1_30_0":0,"deferred_days_general_1_30_0":30,"installments_count_general_1_30_0":1,"min_amount_general_1_0_0":null,"max_amount_general_1_0_0":null,"enabled_general_1_0_0":"yes","deferred_months_general_1_0_0":0,"deferred_days_general_1_0_0":0,"installments_count_general_1_0_0":1,"min_amount_general_1_15_0":null,"max_amount_general_1_15_0":null,"enabled_general_1_15_0":"yes","deferred_months_general_1_15_0":0,"deferred_days_general_1_15_0":15,"installments_count_general_1_15_0":1,"min_amount_general_2_0_0":null,"max_amount_general_2_0_0":null,"enabled_general_2_0_0":"yes","deferred_months_general_2_0_0":0,"deferred_days_general_2_0_0":0,"installments_count_general_2_0_0":2,"min_amount_general_3_0_0":null,"max_amount_general_3_0_0":null,"deferred_months_general_3_0_0":0,"deferred_days_general_3_0_0":0,"installments_count_general_3_0_0":3,"min_amount_general_4_0_0":null,"max_amount_general_4_0_0":null,"enabled_general_4_0_0":"yes","deferred_months_general_4_0_0":0,"deferred_days_general_4_0_0":0,"installments_count_general_4_0_0":4,"min_amount_general_10_0_0":null,"max_amount_general_10_0_0":null,"enabled_general_10_0_0":"yes","deferred_months_general_10_0_0":0,"deferred_days_general_10_0_0":0,"installments_count_general_10_0_0":10,"min_amount_general_12_0_0":null,"max_amount_general_12_0_0":null,"enabled_general_12_0_0":"yes","deferred_months_general_12_0_0":0,"deferred_days_general_12_0_0":0,"installments_count_general_12_0_0":12}';

		$this->assertEquals(json_decode($result, true), $settings_helper->reset_plans(json_decode($this->alma_settings, true)));

		$data = $settings_helper->reset_plans(array(
			"display_cart_eligibility" => "yes",
			'allowed_fee_plans' => array(
				'test' => 'test'
			),
			'live_merchant_id' => 'merchant live id',
			'test_merchant_id' => 'merchant test id',
			'min_amount_general_1_30_0' => 5000,
			'max_amount_general_1_30_0' => 200000,
		));

		$result = $settings_helper->reset_plans(array(
			"display_cart_eligibility" => "yes",
			'allowed_fee_plans' => null,
			'live_merchant_id' =>  null,
			'test_merchant_id' =>  null,
			'min_amount_general_1_30_0'=> null,
			'max_amount_general_1_30_0' =>  null,
		));

		$this->assertEquals($result, $settings_helper->reset_plans($data));
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\SettingsHelper::check_alma_keys
	 *
	 * @return void
	 */
	public function test_alma_keys()
	{
		// Has key ,  no exception
		$settings_helper_builder = new SettingsHelperBuilder();
		$settings_helper = $settings_helper_builder->get_instance();

		$this->assertNull($settings_helper->check_alma_keys(true, false));

		// Has no key ,  exception
		$this->expectExceptionMessage('Alma is almost ready. To get started, <a href="http://example.org/wp-admin/admin.php?page=wc-settings&#038;tab=checkout&#038;section=alma">fill in your API keys</a>.');
		$settings_helper->check_alma_keys(false, true);

	}
}



