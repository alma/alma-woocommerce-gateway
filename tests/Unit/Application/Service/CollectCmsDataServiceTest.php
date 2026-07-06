<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Client\Application\Endpoint\ConfigurationEndpoint;
use Alma\Client\Application\Exception\Endpoint\ConfigurationEndpointException;
use Alma\Gateway\Application\Service\CollectCmsDataService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\CollectCmsDataHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class CollectCmsDataServiceTest extends TestCase {

	private ConfigurationEndpoint $configurationEndpoint;
	private ConfigService $configService;
	private LoggerService $loggerService;
	private FeePlanRepository $feePlanRepository;
	private CollectCmsDataHelper $collectCmsDataHelper;
	private CollectCmsDataService $service;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}

		$this->configurationEndpoint = $this->createMock( ConfigurationEndpoint::class );
		$this->configService         = $this->createMock( ConfigService::class );
		$this->loggerService         = $this->createMock( LoggerService::class );
		$this->feePlanRepository     = $this->createMock( FeePlanRepository::class );
		$this->collectCmsDataHelper  = $this->createMock( CollectCmsDataHelper::class );

		$this->service = new CollectCmsDataService(
			$this->configurationEndpoint,
			$this->configService,
			$this->loggerService,
			$this->feePlanRepository,
			$this->collectCmsDataHelper
		);
	}

	protected function tearDown(): void {
		unset( $_SERVER['HTTP_X_ALMA_SIGNATURE'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * When no date is stored, the URL must be sent and the date saved.
	 */
	public function testMaybeSendCollectDataUrlWhenNoSettingSendsUrlAndSavesDate(): void {
		$url = 'https://example.com/?wc-api=alma_collect_cms_data';
		$this->collectCmsDataHelper->method( 'getCollectCmsDataUrl' )->willReturn( $url );

		$this->configService->method( 'getSetting' )
		                    ->with( CollectCmsDataService::COLLECT_DATA_URL_SENT_AT )
		                    ->willReturn( '' );

		$this->configurationEndpoint->expects( $this->once() )
		                            ->method( 'sendIntegrationsConfigurationsUrl' )
		                            ->with( $url );

		$this->configService->expects( $this->once() )
		                    ->method( 'createSetting' )
		                    ->with( CollectCmsDataService::COLLECT_DATA_URL_SENT_AT, $this->isType( 'string' ) );

		$this->service->sendCollectDataUrl();
	}

	/**
	 * When the stored date is older than 30 days, the URL must be re-sent and the date updated.
	 */
	public function testMaybeSendCollectDataUrlWhenSentAtOlderThan30DaysSendsUrlAndSavesDate(): void {
		$url     = 'https://example.com/?wc-api=alma_collect_cms_data';
		$oldDate = gmdate( 'c', strtotime( '-31 days' ) );
		$this->collectCmsDataHelper->method( 'getCollectCmsDataUrl' )->willReturn( $url );

		$this->configService->method( 'getSetting' )
		                    ->with( CollectCmsDataService::COLLECT_DATA_URL_SENT_AT )
		                    ->willReturn( $oldDate );

		$this->configurationEndpoint->expects( $this->once() )
		                            ->method( 'sendIntegrationsConfigurationsUrl' )
		                            ->with( $url );

		$this->configService->expects( $this->once() )
		                    ->method( 'createSetting' )
		                    ->with( CollectCmsDataService::COLLECT_DATA_URL_SENT_AT, $this->isType( 'string' ) );

		$this->service->sendCollectDataUrl();
	}

	/**
	 * When the stored date is within the last 30 days, nothing must be sent.
	 */
	public function testMaybeSendCollectDataUrlWhenSentAtWithinThirtyDaysDoesNotSend(): void {
		$recentDate = gmdate( 'c', strtotime( '-1 day' ) );

		$this->configService->method( 'getSetting' )
		                    ->with( CollectCmsDataService::COLLECT_DATA_URL_SENT_AT )
		                    ->willReturn( $recentDate );

		$this->configurationEndpoint->expects( $this->never() )
		                            ->method( 'sendIntegrationsConfigurationsUrl' );

		$this->configService->expects( $this->never() )
		                    ->method( 'createSetting' );

		$this->service->sendCollectDataUrl();
	}

	/**
	 * When the stored value is not a valid date, it must be treated as missing and the URL sent.
	 */
	public function testMaybeSendCollectDataUrlWhenSentAtIsInvalidDateSendsUrl(): void {
		$url = 'https://example.com/?wc-api=alma_collect_cms_data';
		$this->collectCmsDataHelper->method( 'getCollectCmsDataUrl' )->willReturn( $url );

		$this->configService->method( 'getSetting' )
		                    ->with( CollectCmsDataService::COLLECT_DATA_URL_SENT_AT )
		                    ->willReturn( 'not-a-valid-date' );

		$this->configurationEndpoint->expects( $this->once() )
		                            ->method( 'sendIntegrationsConfigurationsUrl' )
		                            ->with( $url );

		$this->configService->expects( $this->once() )
		                    ->method( 'createSetting' );

		$this->service->sendCollectDataUrl();
	}

	/**
	 * When the endpoint throws an exception, the date must NOT be saved and the error must be logged.
	 */
	public function testMaybeSendCollectDataUrlWhenExceptionThrownDoesNotSaveDateAndLogsError(): void {
		$this->collectCmsDataHelper->method( 'getCollectCmsDataUrl' )
		                           ->willReturn( 'https://example.com/?wc-api=alma_collect_cms_data' );

		$this->configService->method( 'getSetting' )
		                    ->willReturn( '' );

		$this->configurationEndpoint->method( 'sendIntegrationsConfigurationsUrl' )
		                            ->willThrowException( new ConfigurationEndpointException( 'API error' ) );

		$this->configService->expects( $this->never() )
		                    ->method( 'createSetting' );

		$this->loggerService->expects( $this->once() )
		                    ->method( 'error' )
		                    ->with(
			                    $this->stringContains( 'Failed to send collect CMS data URL to Alma' ),
			                    $this->arrayHasKey( 'exception' )
		                    );

		$this->service->sendCollectDataUrl();
	}

	/**
	 * When the signature header is missing, handle() must return 401.
	 */
	public function testHandleMissingSignatureHeaderReturns401(): void {
		unset( $_SERVER['HTTP_X_ALMA_SIGNATURE'] );

		$capturedData   = null;
		$capturedStatus = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedData, &$capturedStatus ) {
				$capturedData   = $data;
				$capturedStatus = $status;
			}
		);

		$this->service->handle();

		$this->assertSame( 401, $capturedStatus );
		$this->assertArrayHasKey( 'error', $capturedData );
		$this->assertStringContainsString( 'X-Alma-Signature', $capturedData['error'] );
	}

	/**
	 * When the signature is invalid, handle() must return 401 and log a warning.
	 */
	public function testHandleInvalidSignatureReturns401AndLogsWarning(): void {
		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = 'invalid_signature';

		$this->configService->method( 'getMerchantId' )->willReturn( 'merchant_123' );
		$this->configService->method( 'getActiveApiKey' )->willReturn( 'test_api_key' );

		$capturedStatus = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedStatus ) {
				$capturedStatus = $status;
			}
		);

		$this->loggerService->expects( $this->once() )
		                    ->method( 'warning' )
		                    ->with( $this->stringContains( 'signature validation failed' ) );

		$this->service->handle();

		$this->assertSame( 401, $capturedStatus );
	}

	/**
	 * When credentials are missing, handle() must return 401 without attempting HMAC validation.
	 */
	public function testHandleEmptyCredentialsReturns401(): void {
		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = 'some_signature';

		$this->configService->method( 'getMerchantId' )->willReturn( null );
		$this->configService->method( 'getActiveApiKey' )->willReturn( null );

		$capturedStatus = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedStatus ) {
				$capturedStatus = $status;
			}
		);

		$this->loggerService->expects( $this->never() )->method( 'warning' );

		$this->service->handle();

		$this->assertSame( 401, $capturedStatus );
	}

	/**
	 * When the signature is valid, handle() must return 200 with CMS features data.
	 */
	public function testHandleValidSignatureReturnsOkWithCmsData(): void {
		$merchantId = 'merchant_123';
		$apiKey     = 'test_api_key';
		$signature  = hash_hmac( 'sha256', $merchantId, $apiKey );

		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = $signature;

		$this->configService->method( 'getMerchantId' )->willReturn( $merchantId );
		$this->configService->method( 'getActiveApiKey' )->willReturn( $apiKey );
		$this->configService->method( 'isEnabled' )->willReturn( true );
		$this->configService->method( 'getWidgetCartEnabled' )->willReturn( true );
		$this->configService->method( 'getWidgetProductEnabled' )->willReturn( false );
		$this->configService->method( 'isInPageEnabled' )->willReturn( false );
		$this->configService->method( 'isDebug' )->willReturn( false );
		$this->configService->method( 'getExcludedCategories' )->willReturn( array() );

		$this->feePlanRepository->method( 'getAll' )
		                        ->willReturn( new FeePlanListAdapter( array() ) );

		$this->collectCmsDataHelper->method( 'getPaymentMethodPosition' )->willReturn( 1 );
		$this->collectCmsDataHelper->method( 'getSpecificFeatures' )->willReturn( array() );
		$this->collectCmsDataHelper->method( 'isMultisite' )->willReturn( false );

		$capturedData   = null;
		$capturedStatus = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedData, &$capturedStatus ) {
				$capturedData   = $data;
				$capturedStatus = $status;
			}
		);

		$this->service->handle();

		$this->assertSame( 200, $capturedStatus );
		$this->assertIsArray( $capturedData );
		$this->assertArrayHasKey( 'cms_features', $capturedData );
		$this->assertTrue( $capturedData['cms_features']['alma_enabled'] );
		$this->assertArrayHasKey( 'payment_method_position', $capturedData['cms_features'] );
	}

	/**
	 * When the fee plan repository throws an exception, handle() should still return 200
	 * with null for used_fee_plans and log a warning.
	 */
	public function testHandleValidSignatureWithFeePlanExceptionReturns200AndLogsWarning(): void {
		$merchantId = 'merchant_123';
		$apiKey     = 'test_api_key';
		$signature  = hash_hmac( 'sha256', $merchantId, $apiKey );

		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = $signature;

		$this->configService->method( 'getMerchantId' )->willReturn( $merchantId );
		$this->configService->method( 'getActiveApiKey' )->willReturn( $apiKey );
		$this->configService->method( 'isEnabled' )->willReturn( true );
		$this->configService->method( 'getWidgetCartEnabled' )->willReturn( false );
		$this->configService->method( 'getWidgetProductEnabled' )->willReturn( false );
		$this->configService->method( 'isInPageEnabled' )->willReturn( false );
		$this->configService->method( 'isDebug' )->willReturn( false );
		$this->configService->method( 'getExcludedCategories' )->willReturn( array() );

		$this->feePlanRepository->method( 'getAll' )
		                        ->willThrowException( new FeePlanRepositoryException( 'API error' ) );

		$this->collectCmsDataHelper->method( 'getPaymentMethodPosition' )->willReturn( 0 );
		$this->collectCmsDataHelper->method( 'getSpecificFeatures' )->willReturn( array() );
		$this->collectCmsDataHelper->method( 'isMultisite' )->willReturn( false );

		$this->loggerService->expects( $this->once() )
		                    ->method( 'warning' )
		                    ->with( $this->stringContains( 'fee plans' ) );

		$capturedStatus = null;
		$capturedData   = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedData, &$capturedStatus ) {
				$capturedData   = $data;
				$capturedStatus = $status;
			}
		);

		$this->service->handle();

		$this->assertSame( 200, $capturedStatus );
		$this->assertArrayNotHasKey( 'used_fee_plans', $capturedData['cms_features'] );
	}

	/**
	 * When fee plans are available and some are locally enabled, handle() returns them in CMS data.
	 */
	public function testHandleValidSignatureReturnsCmsDataWithEnabledFeePlans(): void {
		$merchantId = 'merchant_123';
		$apiKey     = 'test_api_key';
		$signature  = hash_hmac( 'sha256', $merchantId, $apiKey );

		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = $signature;

		$this->configService->method( 'getMerchantId' )->willReturn( $merchantId );
		$this->configService->method( 'getActiveApiKey' )->willReturn( $apiKey );
		$this->configService->method( 'isEnabled' )->willReturn( true );
		$this->configService->method( 'getWidgetCartEnabled' )->willReturn( false );
		$this->configService->method( 'getWidgetProductEnabled' )->willReturn( false );
		$this->configService->method( 'isInPageEnabled' )->willReturn( false );
		$this->configService->method( 'isDebug' )->willReturn( false );
		$this->configService->method( 'getExcludedCategories' )->willReturn( array() );

		$mockPlan = $this->createMock( FeePlanAdapter::class );
		$mockPlan->method( 'getPlanKey' )->willReturn( 'general_3_0_0' );

		$this->configService->method( 'isFeePlanEnabled' )
		                    ->with( 'general_3_0_0' )
		                    ->willReturn( true );
		$this->configService->method( 'getMinPurchaseAmount' )
		                    ->with( 'general_3_0_0' )
		                    ->willReturn( 5000 );
		$this->configService->method( 'getMaxPurchaseAmount' )
		                    ->with( 'general_3_0_0' )
		                    ->willReturn( 200000 );

		$this->feePlanRepository->method( 'getAll' )
		                        ->willReturn( new FeePlanListAdapter( array( $mockPlan ) ) );

		$this->collectCmsDataHelper->method( 'getPaymentMethodPosition' )->willReturn( 1 );
		$this->collectCmsDataHelper->method( 'getSpecificFeatures' )->willReturn( array() );
		$this->collectCmsDataHelper->method( 'isMultisite' )->willReturn( false );

		$capturedData   = null;
		$capturedStatus = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedData, &$capturedStatus ) {
				$capturedData   = $data;
				$capturedStatus = $status;
			}
		);

		$this->service->handle();

		$this->assertSame( 200, $capturedStatus );
		$this->assertArrayHasKey( 'used_fee_plans', $capturedData['cms_features'] );
		$this->assertArrayHasKey( 'general_3_0_0', $capturedData['cms_features']['used_fee_plans'] );
		$this->assertTrue( $capturedData['cms_features']['used_fee_plans']['general_3_0_0']['enabled'] );
		$this->assertSame( 5000, $capturedData['cms_features']['used_fee_plans']['general_3_0_0']['min_amount'] );
		$this->assertSame( 200000, $capturedData['cms_features']['used_fee_plans']['general_3_0_0']['max_amount'] );
	}

	/**
	 * Verify that handle() returns a cms_info key with CMS and plugin metadata.
	 */
	public function testHandleValidSignatureReturnsCmsInfo(): void {
		$merchantId = 'merchant_123';
		$apiKey     = 'test_api_key';
		$signature  = hash_hmac( 'sha256', $merchantId, $apiKey );

		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = $signature;

		$this->configService->method( 'getMerchantId' )->willReturn( $merchantId );
		$this->configService->method( 'getActiveApiKey' )->willReturn( $apiKey );
		$this->configService->method( 'isEnabled' )->willReturn( false );
		$this->configService->method( 'getWidgetCartEnabled' )->willReturn( false );
		$this->configService->method( 'getWidgetProductEnabled' )->willReturn( false );
		$this->configService->method( 'isInPageEnabled' )->willReturn( false );
		$this->configService->method( 'isDebug' )->willReturn( false );
		$this->configService->method( 'getExcludedCategories' )->willReturn( array() );

		$this->feePlanRepository->method( 'getAll' )
		                        ->willReturn( new FeePlanListAdapter( array() ) );

		$this->collectCmsDataHelper->method( 'getPaymentMethodPosition' )->willReturn( 2 );
		$this->collectCmsDataHelper->method( 'getSpecificFeatures' )->willReturn( array() );
		$this->collectCmsDataHelper->method( 'isMultisite' )->willReturn( false );
		$this->collectCmsDataHelper->method( 'getCmsVersion' )->willReturn( '9.0.0' );
		$this->collectCmsDataHelper->method( 'getThirdPartiesPlugins' )->willReturn(
			array( array( 'name' => 'WooCommerce', 'version' => '9.0.0' ) )
		);
		$this->collectCmsDataHelper->method( 'getThemeName' )->willReturn( 'Storefront' );
		$this->collectCmsDataHelper->method( 'getThemeVersion' )->willReturn( '4.2.0' );

		$capturedData = null;
		Functions\when( 'wp_send_json' )->alias(
			function ( $data, $status ) use ( &$capturedData ) {
				$capturedData = $data;
			}
		);

		$this->service->handle();

		$this->assertArrayHasKey( 'cms_info', $capturedData );
		$cmsInfo = $capturedData['cms_info'];
		$this->assertSame( 'WooCommerce', $cmsInfo['cms_name'] );
		$this->assertSame( '9.0.0', $cmsInfo['cms_version'] );
		$this->assertSame( 'PHP', $cmsInfo['language_name'] );
		$this->assertSame( phpversion(), $cmsInfo['language_version'] );
		$this->assertSame( 'alma/alma-php-client', $cmsInfo['alma_sdk_name'] );
		$this->assertSame( 'Storefront', $cmsInfo['theme_name'] );
		$this->assertSame( '4.2.0', $cmsInfo['theme_version'] );
		$this->assertArrayHasKey( 'alma_plugin_version', $cmsInfo );
		$this->assertArrayHasKey( 'third_parties_plugins', $cmsInfo );
	}

	/**
	 * Verify the WC API endpoint constant value.
	 */
	public function testWcApiEndpointConstantValue(): void {
		$this->assertSame( 'alma_collect_cms_data', CollectCmsDataService::WC_API_ENDPOINT );
	}
}
