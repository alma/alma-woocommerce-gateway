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
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * When no date is stored, the URL must be sent and the date saved.
	 */
	public function testMaybeSendCollectDataUrlWhenNoSettingSendsUrlAndSavesDate(): void {
		$url = 'https://example.com/wp-json/alma/v1/collect-cms-data';
		Functions\when( 'rest_url' )->justReturn( $url );

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
		$url     = 'https://example.com/wp-json/alma/v1/collect-cms-data';
		$oldDate = gmdate( 'c', strtotime( '-31 days' ) );
		Functions\when( 'rest_url' )->justReturn( $url );

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
		$url = 'https://example.com/wp-json/alma/v1/collect-cms-data';
		Functions\when( 'rest_url' )->justReturn( $url );

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
		$url = 'https://example.com/wp-json/alma/v1/collect-cms-data';
		Functions\when( 'rest_url' )->justReturn( $url );

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
	 * Verify the setting key constant value to prevent accidental renames.
	 */
	public function testCollectDataUrlSentAtConstantValue(): void {
		$this->assertSame( 'collect_data_url_sent_at', CollectCmsDataService::COLLECT_DATA_URL_SENT_AT );
	}

	/**
	 * Verify the refresh interval constant value.
	 */
	public function testUrlRefreshIntervalDaysConstantValue(): void {
		$this->assertSame( 30, CollectCmsDataService::URL_REFRESH_INTERVAL_DAYS );
	}
}
