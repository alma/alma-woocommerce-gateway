<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Entity\Form\KeyConfiguration;
use Alma\Gateway\Application\Exception\Service\GatewayConfigurationFormValidatorServiceException;
use Alma\Gateway\Application\Service\GatewayConfigurationFormValidatorService;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use PHPUnit\Framework\TestCase;

class GatewayConfigurationFormValidatorServiceTest extends TestCase {

	private $feePlanRepository;
	private $gatewayConfigurationFormValidatorService;


	public function testValidateWithSameMerchantIdWithoutPlan(): void {
		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 0 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( false );

		$gatewayConfigurationForm = $this->createMock( GatewayConfigurationForm::class );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );

		$this->feePlanRepository->expects( $this->never() )->method( 'getAll' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->never() )->method( 'deleteAll' );

		$this->assertInstanceOf(
			GatewayConfigurationForm::class,
			$this->gatewayConfigurationFormValidatorService->validate( $gatewayConfigurationForm )
		);

	}

	public function testValidateWithSameMerchantId(): void {
		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 3 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( false );

		$gatewayConfigurationForm = $this->createMock( GatewayConfigurationForm::class );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );

		$this->feePlanRepository->expects( $this->once() )->method( 'getAll' );
		$feePlanConfigurationList->expects( $this->once() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->never() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->never() )->method( 'deleteAll' );

		$this->assertInstanceOf(
			GatewayConfigurationForm::class,
			$this->gatewayConfigurationFormValidatorService->validate( $gatewayConfigurationForm )
		);

	}


	public function testValidateWithNewMerchantId(): void {
		$feePlanConfigurationList = $this->createMock( FeePlanConfigurationList::class );
		$feePlanConfigurationList->method( 'count' )->willReturn( 3 );

		$keyConfiguration = $this->createMock( KeyConfiguration::class );
		$keyConfiguration->expects( $this->once() )->method( 'validate' )->willReturn( $keyConfiguration );
		$keyConfiguration->expects( $this->once() )->method( 'isMerchantIdChanged' )->willReturn( true );

		$gatewayConfigurationForm = $this->createMock( GatewayConfigurationForm::class );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getKeyConfiguration' )->willReturn( $keyConfiguration );
		$gatewayConfigurationForm->expects( $this->once() )->method( 'getFeePlanConfigurationList' )->willReturn( $feePlanConfigurationList );

		$feePlanConfigurationList->expects( $this->once() )->method( 'validate' );
		$feePlanConfigurationList->expects( $this->once() )->method( 'reset' );
		$this->feePlanRepository->expects( $this->once() )->method( 'deleteAll' );

		$this->assertInstanceOf(
			GatewayConfigurationForm::class,
			$this->gatewayConfigurationFormValidatorService->validate( $gatewayConfigurationForm )
		);


	}

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

		$this->gatewayConfigurationFormValidatorService->validate( $gatewayConfigurationForm );

	}


	protected function setUp(): void {
		$this->feePlanRepository                        = $this->createMock( FeePlanRepository::class );
		$this->gatewayConfigurationFormValidatorService = new GatewayConfigurationFormValidatorService( $this->feePlanRepository );
	}

	protected function tearDown(): void {
		$this->feePlanRepository = null;
	}
}