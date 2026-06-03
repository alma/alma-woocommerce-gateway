<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Client\Application\Endpoint\ConfigurationEndpoint;
use Alma\Client\Application\Exception\Endpoint\ConfigurationEndpointException;
use Alma\Gateway\Application\Service\CollectCmsDataService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class CollectCmsDataServiceTest extends TestCase {

	private ConfigurationEndpoint $configurationEndpoint;
	private ConfigService $configService;
	private LoggerService $loggerService;
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

		$this->service = new CollectCmsDataService(
			$this->configurationEndpoint,
			$this->configService,
			$this->loggerService
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
		$url = 'https://example.com/wc-api/alma_collect_cms_data';
		Functions\when( 'home_url' )->justReturn( $url );

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
		$url     = 'https://example.com/wc-api/alma_collect_cms_data';
		$oldDate = gmdate( 'c', strtotime( '-31 days' ) );
		Functions\when( 'home_url' )->justReturn( $url );

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
		$url = 'https://example.com/wc-api/alma_collect_cms_data';
		Functions\when( 'home_url' )->justReturn( $url );

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
		$url = 'https://example.com/wc-api/alma_collect_cms_data';
		Functions\when( 'home_url' )->justReturn( $url );

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
	 * When the signature is valid, handle() must return 200 with the expected message.
	 */
	public function testHandleValidSignatureReturnsOk(): void {
		$merchantId = 'merchant_123';
		$apiKey     = 'test_api_key';
		$signature  = hash_hmac( 'sha256', $merchantId, $apiKey );

		$_SERVER['HTTP_X_ALMA_SIGNATURE'] = $signature;

		$this->configService->method( 'getMerchantId' )->willReturn( $merchantId );
		$this->configService->method( 'getActiveApiKey' )->willReturn( $apiKey );

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
		$this->assertSame( 'Data Collection for CMS OK', $capturedData );
	}

	/**
	 * Verify the WC API endpoint constant value.
	 */
	public function testWcApiEndpointConstantValue(): void {
		$this->assertSame( 'alma_collect_cms_data', CollectCmsDataService::WC_API_ENDPOINT );
	}
}
