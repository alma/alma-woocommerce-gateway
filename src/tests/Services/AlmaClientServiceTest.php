<?php

namespace Alma\Woocommerce\Tests\Services;

use Alma\API\Client;
use Alma\API\Endpoints\Payments;
use Alma\API\RequestError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Exceptions\ApiClientException;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\EncryptorHelper;
use Alma\Woocommerce\Services\AlmaClientService;
use Alma\Woocommerce\WcProxy\OptionProxy;
use WP_UnitTestCase;

class AlmaClientServiceTest extends WP_UnitTestCase {

	private $logger;
	private $encryptor_helper;
	private $version_factory;
	private $option_proxy;
	private $cart_helper;
	/**
	 * @var AlmaClientService
	 */
	private $alma_client_service;

	public function set_up() {
		$this->encryptor_helper    = $this->createMock( EncryptorHelper::class );
		$this->version_factory     = $this->createMock( VersionFactory::class );
		$this->cart_helper         = $this->createMock( CartHelper::class );
		$this->option_proxy        = $this->createMock( OptionProxy::class );
		$this->logger              = $this->createMock( AlmaLogger::class );
		$this->alma_client_service = new AlmaClientService(
			$this->encryptor_helper,
			$this->version_factory,
			$this->cart_helper,
			$this->option_proxy,
			$this->logger
		);
	}

	/**
	 * @return void
	 * @dataProvider api_client_exception_data_provider
	 * @throws ApiClientException
	 */
	public function test_get_alma_client_without_mode( $setting, $message ) {
		$this->option_proxy->method( 'get_option' )->willReturn( $setting );
		$this->expectException( ApiClientException::class );
		$this->expectExceptionMessage( $message );
		$this->alma_client_service->get_alma_client();
	}

	public function test_get_eligibility_return_empty_on_error() {
		$this->cart_helper
			->method( 'get_eligible_plans_for_cart' )
			->willReturn( [ 'installments_count' => 3 ] );

		$client_mock = $this->createMock( Client::class );

		$payment_endpoint = $this->createMock( Payments::class );
		$payment_endpoint
			->expects( $this->once() )
			->method( 'eligibility' )
			->with(
				[
					'purchase_amount'  => 24523,
					'queries'          => [ 'installments_count' => 3 ],
					'locale'           => apply_filters( 'alma_eligibility_user_locale', get_locale() ),
					'billing_address'  => [ 'country' => 'FR' ],
					'shipping_address' => [ 'country' => 'UK' ],
				],
				true
			)
			->willThrowException( new RequestError( 'Eligibility Error' ) );
		$client_mock->payments = $payment_endpoint;

		$cart_mock     = $this->createMock( \WC_Cart::class );
		$customer_mock = $this->createMock( \WC_Customer::class );
		$customer_mock->method( 'get_billing_country' )->willReturn( 'FR' );
		$customer_mock->method( 'get_shipping_country' )->willReturn( 'UK' );

		$cart_mock->method( 'get_total' )->willReturn( 245.23 );
		$cart_mock->method( 'get_customer' )->willReturn( $customer_mock );

		$this->assertEquals( [], $this->alma_client_service->get_eligibility( $client_mock, $cart_mock ) );

	}

	public function test_get_eligibility() {
		$this->cart_helper
			->method( 'get_eligible_plans_for_cart' )
			->willReturn( [ 'installments_count' => 3 ] );

		$client_mock = $this->createMock( Client::class );

		$payment_endpoint = $this->createMock( Payments::class );
		$payment_endpoint
			->expects( $this->once() )
			->method( 'eligibility' )
			->with(
				[
					'purchase_amount'  => 24523,
					'queries'          => [ 'installments_count' => 3 ],
					'locale'           => apply_filters( 'alma_eligibility_user_locale', get_locale() ),
					'billing_address'  => [ 'country' => 'FR' ],
					'shipping_address' => [ 'country' => 'UK' ],
				],
				true
			)
			->willReturn( [ 'eligibility' => 'return ' ] );
		$client_mock->payments = $payment_endpoint;

		$cart_mock     = $this->createMock( \WC_Cart::class );
		$customer_mock = $this->createMock( \WC_Customer::class );
		$customer_mock->method( 'get_billing_country' )->willReturn( 'FR' );
		$customer_mock->method( 'get_shipping_country' )->willReturn( 'UK' );

		$cart_mock->method( 'get_total' )->willReturn( 245.23 );
		$cart_mock->method( 'get_customer' )->willReturn( $customer_mock );

		$this->assertEquals( [ 'eligibility' => 'return ' ], $this->alma_client_service->get_eligibility( $client_mock, $cart_mock ) );
	}


	/**
	 * @return void
	 * @dataProvider get_alma_client_data_provider
	 * @throws ApiClientException
	 */
	public function test_get_alma_client_with_config( $setting, $decrypted_api_key ) {
		$this->option_proxy
			->method( 'get_option' )
			->willReturn( $setting );

		$this->encryptor_helper
			->expects( $this->once() )
			->method( 'decrypt' )
			->with( 'encrypted_api_key' )
			->willReturn( $decrypted_api_key );

		$this->assertInstanceOf( Client::class, $this->alma_client_service->get_alma_client() );
	}

	/**
	 * Get Alma Client data provider test/live mode
	 *
	 * @return array[]
	 */
	public function get_alma_client_data_provider() {
		return [
			'Test mode' => [
				'setting'           => [
					'environment'  => 'test',
					'test_api_key' => 'encrypted_api_key',
				],
				'decrypted_api_key' => 'test_api_key',
			],
			'Live mode' => [
				'setting'           => [
					'environment'  => 'live',
					'live_api_key' => 'encrypted_api_key',
				],
				'decrypted_api_key' => 'live_api_key',
			],
		];
	}

	/**
	 * Api Client exception data provider
	 * @return array[]
	 */
	public function api_client_exception_data_provider() {
		return [
			'Without Mode'         => [
				'setting' => [],
				'message' => 'No mode set',
			],
			'Without test Api Key' => [
				'setting' => [ 'environment' => 'test' ],
				'message' => 'Test api key not set',
			],
			'Without live Api Key' => [
				'setting' => [ 'environment' => 'live' ],
				'message' => 'Live api key not set',
			]
		];
	}

	private function get_setting_mock_array() {
		return [
			"enabled"                                    => "yes",
			"payment_upon_trigger_enabled"               => "no",
			"payment_upon_trigger_event"                 => "completed",
			"payment_upon_trigger_display_text"          => "at_shipping",
			"selected_fee_plan"                          => "general_6_0_0",
			"enabled_general_3_0_0"                      => "yes",
			"title_alma_in_page"                         => "Pay in installments",
			"description_alma_in_page"                   => "Fast and secure payment by credit card",
			"title_alma_in_page_pay_now"                 => "Pay by credit card",
			"description_alma_in_page_pay_now"           => "Fast and secured payments",
			"title_alma_in_page_pay_later"               => "Pay later",
			"description_alma_in_page_pay_later"         => "Fast and secure payment by credit card",
			"title_alma_in_page_pnx_plus_4"              => "Pay with financing",
			"description_alma_in_page_pnx_plus_4"        => "Fast and secure payment by credit card",
			"title_alma"                                 => "Pay in installments",
			"description_alma"                           => "Fast and secure payment by credit card",
			"title_alma_pay_now"                         => "Pay by credit card",
			"description_alma_pay_now"                   => "Fast and secured payments",
			"title_alma_pay_later"                       => "Pay later",
			"description_alma_pay_later"                 => "Fast and secure payment by credit card",
			"title_alma_pnx_plus_4"                      => "Pay with financing",
			"description_alma_pnx_plus_4"                => "Fast and secure payment by credit card",
			"title_blocks_alma_in_page"                  => "Pay in installments",
			"description_blocks_alma_in_page"            => "Fast and secure payment by credit card",
			"title_blocks_alma_in_page_pay_now"          => "Pay by credit card",
			"description_blocks_alma_in_page_pay_now"    => "Fast and secured payments",
			"title_blocks_alma_in_page_pay_later"        => "Pay later",
			"description_blocks_alma_in_page_pay_later"  => "Fast and secure payment by credit card",
			"title_blocks_alma"                          => "Pay in installments",
			"description_blocks_alma"                    => "Fast and secure payment by credit card",
			"title_blocks_alma_pay_now"                  => "Pay by credit card",
			"description_blocks_alma_pay_now"            => "Fast and secured payments",
			"title_blocks_alma_pay_later"                => "Pay later",
			"description_blocks_alma_pay_later"          => "Fast and secure payment by credit card",
			"title_blocks_alma_pnx_plus_4"               => "Pay with financing",
			"description_blocks_alma_pnx_plus_4"         => "Fast and secure payment by credit card",
			"display_cart_eligibility"                   => "yes",
			"display_product_eligibility"                => "yes",
			"variable_product_price_query_selector"      => "form.variations_form div.woocommerce-variation-price span.woocommerce-Price-amount bdi",
			"variable_product_sale_price_query_selector" => "form.variations_form div.woocommerce-variation-price ins span.woocommerce-Price-amount bdi",
			"variable_product_check_variations_event"    => "check_variations",
			"excluded_products_list"                     => "",
			"cart_not_eligible_message_gift_cards"       => "Some products cannot be paid with monthly or deferred installments",
			"live_api_key"                               => "",
			"test_api_key"                               => "encrypted_api_key",
			"environment"                                => "test",
			"share_of_checkout_enabled"                  => "no",
			"debug"                                      => "yes",
			"keys_validity"                              => "yes",
			"display_in_page"                            => "no",
			"use_blocks_template"                        => "yes",
			"allowed_fee_plans"                          => "a:8:{...}",
			"live_merchant_id"                           => null,
			"test_merchant_id"                           => "merchant_11xYpTY1GTkww5uWFKFdOllK82S1r7j5v5",
			"test_merchant_name"                         => "ECOM Inte Shop",
			"min_amount_general_1_0_0"                   => 100,
			"max_amount_general_1_0_0"                   => 200000,
			"enabled_general_1_0_0"                      => "no",
			"deferred_months_general_1_0_0"              => 0,
			"deferred_days_general_1_0_0"                => 0,
			"installments_count_general_1_0_0"           => 1,
			"min_amount_general_2_0_0"                   => 5000,
			"max_amount_general_2_0_0"                   => 200000,
			"enabled_general_2_0_0"                      => "no",
			"deferred_months_general_2_0_0"              => 0,
			"deferred_days_general_2_0_0"                => 0,
			"installments_count_general_2_0_0"           => 2,
			"min_amount_general_3_0_0"                   => 5000,
			"max_amount_general_3_0_0"                   => 200000,
			"deferred_months_general_3_0_0"              => 0,
			"deferred_days_general_3_0_0"                => 0,
			"installments_count_general_3_0_0"           => 3,
			"min_amount_general_1_0_1"                   => 5000,
			"max_amount_general_1_0_1"                   => 200000,
			"enabled_general_1_0_1"                      => "no",
			"deferred_months_general_1_0_1"              => 1,
			"deferred_days_general_1_0_1"                => 0,
			"installments_count_general_1_0_1"           => 1,
			"min_amount_general_1_15_0"                  => 5000,
			"max_amount_general_1_15_0"                  => 200000,
			"enabled_general_1_15_0"                     => "yes",
			"deferred_months_general_1_15_0"             => 0,
			"deferred_days_general_1_15_0"               => 15,
			"installments_count_general_1_15_0"          => 1,
			"min_amount_general_4_0_0"                   => 5000,
			"max_amount_general_4_0_0"                   => 200000,
			"enabled_general_4_0_0"                      => "no",
			"deferred_months_general_4_0_0"              => 0,
			"deferred_days_general_4_0_0"                => 0,
			"installments_count_general_4_0_0"           => 4,
			"min_amount_general_6_0_0"                   => 40000,
			"max_amount_general_6_0_0"                   => 200000,
			"enabled_general_6_0_0"                      => "yes",
			"deferred_months_general_6_0_0"              => 0,
			"deferred_days_general_6_0_0"                => 0,
			"installments_count_general_6_0_0"           => 6,
			"min_amount_general_10_0_0"                  => 5000,
			"max_amount_general_10_0_0"                  => 200000,
			"enabled_general_10_0_0"                     => "no",
			"deferred_months_general_10_0_0"             => 0,
			"deferred_days_general_10_0_0"               => 0,
			"installments_count_general_10_0_0"          => 10,
		];
	}
}