<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Service;

use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Provider\PaymentProviderFactory;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\OrderRepositoryException;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Infrastructure\Service\GatewayService;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GatewayServiceTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $paymentProviderFactoryMock;
	private $gatewayRepositoryMock;
	private $assetsServiceMock;
	private $businessEventsServiceMock;
	private $loggerServiceMock;
	private GatewayService $gatewayService;

	protected function setUp(): void {
		parent::setUp();

		$this->paymentProviderFactoryMock = $this->createMock( PaymentProviderFactory::class );
		$this->gatewayRepositoryMock      = $this->createMock( GatewayRepository::class );
		$this->assetsServiceMock          = $this->createMock( AssetsService::class );
		$this->businessEventsServiceMock  = $this->createMock( BusinessEventsService::class );
		$this->loggerServiceMock          = $this->createMock( LoggerService::class );

		$this->gatewayService = new GatewayService(
			$this->paymentProviderFactoryMock,
			$this->gatewayRepositoryMock,
			$this->assetsServiceMock,
			$this->businessEventsServiceMock,
			$this->loggerServiceMock
		);
	}

	protected function tearDown(): void {
		Mockery::resetContainer();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test that when BusinessEventsService throws a BusinessEventsServiceException,
	 * the exception is caught and logged as debug instead of being re-thrown.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWoocommerceOrderStatusChangedLogsDebugOnBusinessEventsServiceException(): void {
		$orderId   = 42;
		$oldStatus = 'pending';
		$newStatus = 'processing';

		$orderMock = Mockery::mock( OrderAdapter::class );
		$orderMock->shouldReceive( 'isRefundable' )->never();

		$orderRepositoryMock = Mockery::mock( OrderRepository::class );
		$orderRepositoryMock->shouldReceive( 'getById' )
		                    ->with( $orderId )
		                    ->andReturn( $orderMock );

		$containerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )
		              ->with( OrderRepository::class )
		              ->andReturn( $orderRepositoryMock );

		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$pluginMock->shouldReceive( 'get_container' )
		           ->andReturn( $containerMock );

		$this->businessEventsServiceMock->expects( $this->once() )
		                                ->method( 'onOrderConfirmed' )
		                                ->with( $oldStatus, $newStatus, $orderMock )
		                                ->willThrowException( new BusinessEventsServiceException( 'Test error message' ) );

		$this->loggerServiceMock->expects( $this->once() )
		                        ->method( 'debug' )
		                        ->with( 'Test error message' );

		// Should NOT throw an exception
		$this->gatewayService->woocommerceOrderStatusChanged( $orderId, $oldStatus, $newStatus );
	}

	/**
	 * Test that when BusinessEventsService does not throw,
	 * the logger debug is not called.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWoocommerceOrderStatusChangedDoesNotLogOnSuccess(): void {
		$orderId   = 42;
		$oldStatus = 'pending';
		$newStatus = 'processing';

		$orderMock = Mockery::mock( OrderAdapter::class );

		$orderRepositoryMock = Mockery::mock( OrderRepository::class );
		$orderRepositoryMock->shouldReceive( 'getById' )
		                    ->with( $orderId )
		                    ->andReturn( $orderMock );

		$containerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )
		              ->with( OrderRepository::class )
		              ->andReturn( $orderRepositoryMock );

		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$pluginMock->shouldReceive( 'get_container' )
		           ->andReturn( $containerMock );

		$this->businessEventsServiceMock->expects( $this->once() )
		                                ->method( 'onOrderConfirmed' )
		                                ->with( $oldStatus, $newStatus, $orderMock );

		$this->loggerServiceMock->expects( $this->never() )
		                        ->method( 'debug' );

		$this->gatewayService->woocommerceOrderStatusChanged( $orderId, $oldStatus, $newStatus );
	}

	/**
	 * Test that when OrderRepository throws OrderRepositoryException,
	 * a GatewayServiceException is thrown.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWoocommerceOrderStatusChangedThrowsGatewayServiceExceptionOnOrderNotFound(): void {
		$orderId   = 999;
		$oldStatus = 'pending';
		$newStatus = 'processing';

		$orderRepositoryMock = Mockery::mock( OrderRepository::class );
		$orderRepositoryMock->shouldReceive( 'getById' )
		                    ->with( $orderId )
		                    ->andThrow( new OrderRepositoryException( 'Order not found' ) );

		$containerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )
		              ->with( OrderRepository::class )
		              ->andReturn( $orderRepositoryMock );

		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$pluginMock->shouldReceive( 'get_container' )
		           ->andReturn( $containerMock );

		$this->expectException( GatewayServiceException::class );
		$this->expectExceptionMessage( 'Order not found' );

		$this->gatewayService->woocommerceOrderStatusChanged( $orderId, $oldStatus, $newStatus );
	}
}

