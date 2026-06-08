<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Service\CollectCmsDataService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\FraudService;
use Alma\Gateway\Application\Service\IpnService;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Plugin\Infrastructure\Helper\NavigationHelperInterface;
use Brain\Monkey;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class IpnServiceTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private ConfigService $configService;
	private FraudService $fraudService;
	private PaymentProvider $paymentProvider;
	private NavigationHelperInterface $navigationHelper;
	private IpnHelper $ipnHelper;
	private LoggerService $loggerService;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->configService    = $this->createMock( ConfigService::class );
		$this->fraudService     = $this->createMock( FraudService::class );
		$this->paymentProvider  = $this->createMock( PaymentProvider::class );
		$this->navigationHelper = $this->createMock( NavigationHelperInterface::class );
		$this->ipnHelper        = $this->createMock( IpnHelper::class );
		$this->loggerService    = $this->createMock( LoggerService::class );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	private function buildService(): IpnService {
		return new IpnService(
			$this->configService,
			$this->fraudService,
			$this->paymentProvider,
			$this->navigationHelper,
			$this->ipnHelper,
			$this->loggerService
		);
	}

	/**
	 * When not in live mode, sendCollectDataUrl must NOT be called.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testSendCollectDataUrlOnlyForLiveModeDoesNothingWhenNotLive(): void {
		$configServiceMock = Mockery::mock( ConfigService::class );
		$configServiceMock->shouldReceive( 'isLiveMode' )
		                  ->once()
		                  ->andReturn( false );

		$collectCmsDataServiceMock = Mockery::mock( CollectCmsDataService::class );
		$collectCmsDataServiceMock->shouldNotReceive( 'sendCollectDataUrl' );

		$containerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )
		              ->with( ConfigService::class )
		              ->andReturn( $configServiceMock );

		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$pluginMock->shouldReceive( 'get_container' )
		           ->andReturn( $containerMock );

		$this->buildService()->sendCollectDataUrlOnlyForLiveMode();
	}

	/**
	 * When in live mode, sendCollectDataUrl must be called exactly once.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testSendCollectDataUrlOnlyForLiveModeCallsSendCollectDataUrlWhenLive(): void {
		$configServiceMock = Mockery::mock( ConfigService::class );
		$configServiceMock->shouldReceive( 'isLiveMode' )
		                  ->once()
		                  ->andReturn( true );

		$collectCmsDataServiceMock = Mockery::mock( CollectCmsDataService::class );
		$collectCmsDataServiceMock->shouldReceive( 'sendCollectDataUrl' )
		                          ->once();

		$containerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )
		              ->with( ConfigService::class )
		              ->andReturn( $configServiceMock );
		$containerMock->shouldReceive( 'get' )
		              ->with( CollectCmsDataService::class )
		              ->andReturn( $collectCmsDataServiceMock );

		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$pluginMock->shouldReceive( 'get_container' )
		           ->andReturn( $containerMock );

		$this->buildService()->sendCollectDataUrlOnlyForLiveMode();
	}
}
