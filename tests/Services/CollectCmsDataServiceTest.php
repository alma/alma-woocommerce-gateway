<?php

namespace Alma\Woocommerce\Tests\Services;

use Alma\API\Client;
use Alma\API\Endpoints\Configuration;
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\MerchantData\CmsFeatures;
use Alma\API\Entities\MerchantData\CmsInfo;
use Alma\API\Lib\PayloadFormatter;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Exceptions\AlmaInvalidSignatureException;
use Alma\Woocommerce\Helpers\FeePlanHelper;
use Alma\Woocommerce\Helpers\SecurityHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Services\CollectCmsDataService;
use Alma\Woocommerce\WcProxy\FunctionsProxy;
use Alma\Woocommerce\WcProxy\OptionProxy;
use Alma\Woocommerce\WcProxy\PaymentGatewaysProxy;
use Alma\Woocommerce\WcProxy\ThemeProxy;
use WP_UnitTestCase;
use function PHPUnit\Framework\assertNull;

class CollectCmsDataServiceTest extends WP_UnitTestCase {
	protected $collect_cms_data_service;
	protected $alma_logger_mock;
	protected $alma_settings_mock;
	protected $security_helper_mock;
	protected $payload_formatter;
	protected $option_proxy_mock;
	protected $theme_proxy_mock;
	protected $functions_proxy_mock;
	protected $fee_plan_mock;
	protected $tools_helper_mock;
	protected $payment_gateways_proxy;

	public function set_up() {
		$this->alma_logger_mock   = $this->createMock( AlmaLogger::class );
		$this->alma_settings_mock = $this->createMock( AlmaSettings::class );
		$this->alma_settings_mock->method( 'is_enabled' )->willReturn( true );
		$this->alma_settings_mock->display_cart_eligibility    = 'yes';
		$this->alma_settings_mock->display_product_eligibility = 'no';
		$this->alma_settings_mock->display_in_page             = 'no';
		$this->alma_settings_mock->debug                       = 'yes';
		$this->alma_settings_mock->method( 'is_plan_enabled' )->willReturn( true );
		$this->alma_settings_mock->method( 'get_min_amount' )->willReturn( 0 );
		$this->alma_settings_mock->method( 'get_max_amount' )->willReturn( 1000 );
		$this->fee_plan_mock = $this->createMock( FeePlan::class );
		$this->fee_plan_mock->method( 'getPlanKey' )->willReturn( 'general_1_0_0' );
		$this->alma_settings_mock->allowed_fee_plans      = [ $this->fee_plan_mock ];
		$this->alma_settings_mock->fee_plan_helper        = $this->createMock( FeePlanHelper::class );
		$this->alma_settings_mock->excluded_products_list = [];
		$this->security_helper_mock                       = $this->createMock( SecurityHelper::class );
		$this->payload_formatter                          = new PayloadFormatter();
		$this->option_proxy_mock                          = $this->createMock( OptionProxy::class );
		$this->theme_proxy_mock                           = $this->createMock( ThemeProxy::class );
		$this->theme_proxy_mock->method( 'get_name' )->willReturn( 'Storefront' );
		$this->theme_proxy_mock->method( 'get_version' )->willReturn( 'v.4.5' );
		$this->functions_proxy_mock = $this->createMock( FunctionsProxy::class );
		$this->tools_helper_mock    = $this->createMock( ToolsHelper::class );


		$this->payment_gateways_proxy = $this->get_payment_gateways();

		$this->collect_cms_data_service = new CollectCmsDataService(
			$this->alma_settings_mock,
			$this->alma_logger_mock,
			$this->payload_formatter,
			$this->security_helper_mock,
			$this->option_proxy_mock,
			$this->theme_proxy_mock,
			$this->functions_proxy_mock,
			$this->tools_helper_mock,
			$this->payment_gateways_proxy
		);
	}

	public function tear_down() {
		PaymentGatewaysProxy::reset_instance();
	}

	public function test_send_url() {
		$this->alma_settings_mock->alma_client                = $this->createMock( Client::class );
		$this->alma_settings_mock->alma_client->configuration = $this->createMock( Configuration::class );

		$this->tools_helper_mock->expects( $this->once() )
		                        ->method( 'url_for_webhook' )
		                        ->with( CollectCmsDataService::COLLECT_URL )
		                        ->willReturn( 'http://example.com/woocommerce_api_alma_collect_data_url' );

		$this->alma_settings_mock->alma_client->configuration->expects( $this->once() )
		                                                     ->method( 'sendIntegrationsConfigurationsUrl' )
		                                                     ->with( 'http://example.com/woocommerce_api_alma_collect_data_url' );

		assertNull( $this->collect_cms_data_service->send_url() );
	}

//	public function test_handle_collect_cms_data_without_signature_header() {
//		$this->alma_logger_mock->expects( $this->once() )
//		                       ->method( 'error' )
//		                       ->with( "Header key X-Alma-Signature doesn't exist" );
//
//		$this->functions_proxy_mock->expects( $this->once() )
//		                           ->method( 'send_http_response' )
//		                           ->with( array( 'error' => 'Header key X-Alma-Signature doesn\'t exist' ), 403 );
//		$this->security_helper_mock->expects( $this->never() )->method( 'validate_collect_data_signature' );
//
//		$this->assertNull( $this->collect_cms_data_service->handle_collect_cms_data() );
//	}
//
//	public function test_handle_collect_cms_data_with_invalid_signature() {
//		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = 'invalid_signature';
//
//		$this->security_helper_mock->expects( $this->once() )
//		                           ->method( 'validate_collect_data_signature' )
//		                           ->willThrowException( new AlmaInvalidSignatureException( "Invalid signature" ) );
//
//		$this->alma_logger_mock->expects( $this->once() )
//		                       ->method( 'error' )
//		                       ->with( "Invalid signature" );
//
//		$this->functions_proxy_mock->expects( $this->once() )
//		                           ->method( 'send_http_response' )
//		                           ->with( array( 'error' => 'Invalid signature' ), 403 );
//
//		$this->payload_formatter_mock->expects( $this->never() )->method( 'formatConfigurationPayload' );
//
//		$this->assertNull( $this->collect_cms_data_service->handle_collect_cms_data() );
//
//	}

	/**
	 * @dataProvider get_auto_update_plugins_settings
	 */
	public function test_handle_collect_cms_data_with_valid_signature( $specific_features, $auto_update_plugins, $gateway_order ) {
//		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = 'valid_signature';
//
//		$this->security_helper_mock->expects( $this->once() )
//		                           ->method( 'validate_collect_data_signature' );

		$valueMap = [
			[ 'active_plugins', false, [] ],
			[ 'woocommerce_version', false, 'v.4.2' ],
			[ 'auto_update_plugins', [], $auto_update_plugins ],
			[ 'woocommerce_gateway_order', [], $gateway_order ]
		];
		$this->option_proxy_mock->method( 'get_option' )->willReturnMap( $valueMap );
		$paymentMethodsList = [
			[ 'name' => 'cheque', 'position' => 0 ],
			[ 'name' => 'bacs', 'position' => 1 ],
			[ 'name' => 'alma', 'position' => 2 ],
			[ 'name' => 'paypal', 'position' => 3 ]
		];
		if ( ! empty( $gateway_order ) ) {
			$paymentMethodsList = [
				[ 'name' => 'cheque', 'position' => 1 ],
				[ 'name' => 'bacs', 'position' => 2 ],
				[ 'name' => 'alma', 'position' => 0 ],
				[ 'name' => 'paypal', 'position' => 3 ]
			];;
		}
		$expectedData = [
			"cms_info"     => [
				"cms_name"              => "WooCommerce",
				"cms_version"           => "v.4.2",
				"third_parties_plugins" => [],
				"theme_name"            => "Storefront",
				"theme_version"         => "v.4.5",
				"language_name"         => "PHP",
				"language_version"      => phpversion(),
				"alma_plugin_version"   => ALMA_VERSION,
				"alma_sdk_version"      => Client::VERSION,
				"alma_sdk_name"         => "alma/alma-php-client"
			],
			"cms_features" => [
				"alma_enabled"             => true,
				"widget_cart_activated"    => true,
				"widget_product_activated" => false,
				"used_fee_plans"           => [
					'general_1_0_0' => [
						'enabled'    => true,
						'min_amount' => 0,
						'max_amount' => 1000
					]
				],
				"in_page_activated"        => false,
				"log_activated"            => true,
				"excluded_categories"      => [],
				"payment_methods_list"     => $paymentMethodsList,
				"payment_method_position"  => 3,
				"specific_features"        => $specific_features,
				"is_multisite"             => false,
			]
		];
		$this->functions_proxy_mock->expects( $this->once() )
		                           ->method( 'send_http_response' )
		                           ->with( $expectedData, 200 );


		$this->assertNull( $this->collect_cms_data_service->handle_collect_cms_data() );
	}

	public function get_auto_update_plugins_settings() {
		return [
			'Auto update plugins key does not exist'                     => [
				'specific_features'   => [ null ],
				'auto_update_plugins' => [],
				'gateway_order'       => []
			],
			'Auto update plugins key exists and nor contain Alma plugin' => [
				'specific_features'   => [ null ],
				'auto_update_plugins' => [ 'woocommerce/woocommerce.php' ],
				'gateway_order'       => []

			],
			'Auto update plugins key exists and contain Alma plugin'     => [
				'specific_features'   => [ 'auto_update' ],
				'auto_update_plugins' => [
					'woocommerce/woocommerce.php',
					'alma-woocommerce-gateway/alma-gateway-for-woocommerce.php'
				],
				'gateway_order'       => []
			],
			'gateway order is not empty'                                 => [
				'specific_features'   => [ null ],
				'auto_update_plugins' => [],
				'gateway_order'       => [
					'cheque' => 1,
					'bacs'   => 2,
					'alma'   => 0,
					'paypal' => 3,
				]
			],

		];
	}

	/**
	 * @return PaymentGatewaysProxy
	 */
	private function get_payment_gateways() {
		$cheque_mock     = $this->createMock( \WC_Payment_Gateway::class );
		$cheque_mock->id = 'cheque';

		$bacs_mock     = $this->createMock( \WC_Payment_Gateway::class );
		$bacs_mock->id = 'bacs';

		$alma_in_page_mock     = $this->createMock( \WC_Payment_Gateway::class );
		$alma_in_page_mock->id = 'alma_in_page';

		$alma_mock     = $this->createMock( \WC_Payment_Gateway::class );
		$alma_mock->id = 'alma';

		$paypal_mock     = $this->createMock( \WC_Payment_Gateway::class );
		$paypal_mock->id = 'paypal';

		$payment_gateways = [
			'cheque'       => $cheque_mock,
			'bacs'         => $bacs_mock,
			'alma_in_page' => $alma_in_page_mock,
			'alma'         => $alma_mock,
			'paypal'       => $paypal_mock
		];

		$wc_payment_gateway = $this->createMock( \WC_Payment_Gateways::class );

		$wc_payment_gateway->method( 'payment_gateways' )
		                   ->willReturn( $payment_gateways );

		return PaymentGatewaysProxy::get_instance( $wc_payment_gateway );
	}
}