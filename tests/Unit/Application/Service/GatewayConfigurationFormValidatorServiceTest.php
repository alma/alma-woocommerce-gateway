<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Entity\Form\KeyConfiguration;
use Alma\Gateway\Application\Exception\Service\GatewayConfigurationFormValidatorServiceException;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\GatewayConfigurationFormValidatorService;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
class GatewayConfigurationFormValidatorServiceTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $feePlanRepository;
	private $gatewayConfigurationForm;
	private $configService;
	private $pluginHelper;
	private GatewayConfigurationFormValidatorService $gatewayConfigurationFormValidatorService;

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testValidateWithSameMerchantIdWithoutPlan(): void {
		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 0 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( false );

		$this->gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$this->gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );
		$this->pluginHelper->shouldReceive('isConfigured')->andReturn(false);
		$this->feePlanRepository->expects( $this->never() )->method( 'getAll' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->never() )->method( 'deleteAll' );

		$this->assertInstanceOf(
			GatewayConfigurationForm::class,
			$this->gatewayConfigurationFormValidatorService->validate( $this->gatewayConfigurationForm, $this->feePlanRepository )
		);

	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testValidateWithSameMerchantId(): void {
		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 3 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( false );

		$this->gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$this->gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );
		$this->pluginHelper->shouldReceive('isConfigured')->andReturn(false);
		$this->feePlanRepository->expects( $this->once() )->method( 'getAll' );
		$feePlanConfigurationList->expects( $this->once() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->never() )->method( 'deleteAll' );

		$this->assertInstanceOf(
			GatewayConfigurationForm::class,
			$this->gatewayConfigurationFormValidatorService->validate( $this->gatewayConfigurationForm, $this->feePlanRepository )
		);

	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testValidateWithNewMerchantId(): void {
		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 3 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( true );

		$this->pluginHelper->shouldReceive('isConfigured')->andReturn(true);

		$this->gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$this->gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );

		$feePlanConfigurationList->expects( $this->once() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->once() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->once() )->method( 'deleteAll' );

		$this->assertInstanceOf(
			GatewayConfigurationForm::class,
			$this->gatewayConfigurationFormValidatorService->validate( $this->gatewayConfigurationForm )
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testValidateApiResponseError(): void {
		$this->expectException( GatewayConfigurationFormValidatorServiceException::class );
		$this->feePlanRepository->method( 'getAll' )->willThrowException( new FeePlanRepositoryException( 'API error' ) );

		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 3 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( false );

		$gatewayConfigurationForm = $this->createMock( GatewayConfigurationForm::class );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );

		$feePlanConfigurationList->expects( $this->never() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->never() )->method( 'deleteAll' );
		$this->pluginHelper->shouldReceive('isConfigured')->andReturn(false);

		$this->gatewayConfigurationFormValidatorService->validate( $gatewayConfigurationForm );
	}

	protected function setUp(): void {
		parent::setUp();
		$this->feePlanRepository                        = $this->createMock( FeePlanRepository::class );
		$this->gatewayConfigurationForm                 = $this->createMock( GatewayConfigurationForm::class );
		$this->configService                            = $this->createMock( ConfigService::class );
		$this->pluginHelper = Mockery::mock('alias:' . PluginHelper::class);

		$this->gatewayConfigurationFormValidatorService = new GatewayConfigurationFormValidatorService();
		$this->gatewayConfigurationFormValidatorService->setConfigService( $this->configService );
		$this->gatewayConfigurationFormValidatorService->setFeePlanRepository( $this->feePlanRepository );
	}

	protected function tearDown(): void {
		Mockery::resetContainer();
		Mockery::close();
		$this->feePlanRepository = null;
		$this->gatewayConfigurationForm = null;
		$this->configService = null;
		parent::tearDown();
	}
}
