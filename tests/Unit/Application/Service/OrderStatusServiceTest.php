<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Provider\PaymentProviderFactory;
use Alma\Gateway\Application\Service\OrderStatusService;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Plugin\Infrastructure\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class OrderStatusServiceTest extends TestCase {

	public function setUp(): void {
		Monkey\setUp();
		parent::setUp();
	}

	public function tearDown(): void {
		\Mockery::close();
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testInitSendOrderStatusHookWithGoodParams() {
		// Given
		$paymentProviderFactoryMock = $this->createMock( PaymentProviderFactory::class );
		$orderRepositoryMock        = $this->createMock( OrderRepositoryInterface::class );
		$orderStatusService         = new OrderStatusService(
			$paymentProviderFactoryMock,
			$orderRepositoryMock
		);
		// Then
		Functions\expect( 'add_action' )
			->once()
			->withArgs( function ( $event, $callback, $priority, $acceptedArgs ) use ( $orderStatusService ) {
				$this->assertSame( 'woocommerce_order_status_changed', $event );
				$this->assertSame( 11, $priority );
				$this->assertSame( 3, $acceptedArgs );

				$this->assertIsArray( $callback );
				$this->assertSame( $orderStatusService, $callback[0] );
				$this->assertSame( 'sendOrderStatus', $callback[1] );

				return true;
			} );


		// When
		$orderStatusService->initSendOrderStatusHook();
	}

	public function testSendOrderStatusDirectReturnForNonOrder() {

		$paymentProviderFactoryMock = $this->createMock( PaymentProviderFactory::class );

		$orderRepositoryMock = $this->createMock( OrderRepository::class );
		$orderRepositoryMock->expects( $this->once() )->method( 'getById' )->willThrowException( new ProductRepositoryException( 'Order not found' ) );


		$orderStatusService = \Mockery::mock(
			OrderStatusService::class,
			[ $paymentProviderFactoryMock, $orderRepositoryMock ]
		)->makePartial();

		$this->assertNull( $orderStatusService->sendOrderStatus( 1, 'pending', 'completed' ) );
	}

	public function testSendOrderStatusNotCallSendForNonAlmaOrder() {
		$nonAlmaOrderMock = $this->createMock( OrderAdapter::class );
		$nonAlmaOrderMock->expects( $this->once() )->method( 'isPaidWithAlma' )->willReturn( false );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->never() )->method( 'addOrderStatusByMerchantOrderReference' );

		$paymentProviderFactoryMock = $this->createMock( PaymentProviderFactory::class );

		$orderRepositoryMock = $this->createMock( OrderRepository::class );
		$orderRepositoryMock->expects( $this->once() )->method( 'getById' )->willReturn( $nonAlmaOrderMock );


		$orderStatusService = \Mockery::mock(
			OrderStatusService::class,
			[ $paymentProviderFactoryMock, $orderRepositoryMock ]
		)->makePartial();
		$ref                = new \ReflectionProperty( OrderStatusService::class, 'paymentProvider' );
		$ref->setAccessible( true );
		$ref->setValue( $orderStatusService, $paymentProvider );

		$this->assertNull( $orderStatusService->sendOrderStatus( 1, 'pending', 'completed' ) );
	}

	public function testSendOrderStatusCallSendForAlmaOrderComplete() {
		$almaOrder = $this->createMock( OrderAdapter::class );
		$almaOrder->expects( $this->once() )->method( 'isPaidWithAlma' )->willReturn( true );
		$almaOrder->expects( $this->once() )->method( 'getPaymentId' )->willReturn( 'payment_123' );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->once() )->method( 'addOrderStatusByMerchantOrderReference' )->with(
			'payment_123',
			1,
			'completed',
			true
		);

		$paymentProviderFactoryMock = $this->createMock( PaymentProviderFactory::class );

		$orderRepositoryMock = $this->createMock( OrderRepository::class );
		$orderRepositoryMock->expects( $this->once() )->method( 'getById' )->willReturn( $almaOrder );


		$orderStatusService = \Mockery::mock(
			OrderStatusService::class,
			[ $paymentProviderFactoryMock, $orderRepositoryMock ]
		)->makePartial();
		$ref                = new \ReflectionProperty( OrderStatusService::class, 'paymentProvider' );
		$ref->setAccessible( true );
		$ref->setValue( $orderStatusService, $paymentProvider );

		$this->assertNull( $orderStatusService->sendOrderStatus( 1, 'pending', 'completed' ) );
	}

	public function testSendOrderStatusCallSendForAlmaOrderProcessing() {
		$almaOrder = $this->createMock( OrderAdapter::class );
		$almaOrder->expects( $this->once() )->method( 'isPaidWithAlma' )->willReturn( true );
		$almaOrder->expects( $this->once() )->method( 'getPaymentId' )->willReturn( 'payment_123' );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->once() )->method( 'addOrderStatusByMerchantOrderReference' )->with(
			'payment_123',
			1,
			'processing',
			false
		);

		$paymentProviderFactoryMock = $this->createMock( PaymentProviderFactory::class );

		$orderRepositoryMock = $this->createMock( OrderRepository::class );
		$orderRepositoryMock->expects( $this->once() )->method( 'getById' )->willReturn( $almaOrder );


		$orderStatusService = \Mockery::mock(
			OrderStatusService::class,
			[ $paymentProviderFactoryMock, $orderRepositoryMock ]
		)->makePartial();
		$ref                = new \ReflectionProperty( OrderStatusService::class, 'paymentProvider' );
		$ref->setAccessible( true );
		$ref->setValue( $orderStatusService, $paymentProvider );

		$this->assertNull( $orderStatusService->sendOrderStatus( 1, 'pending', 'processing' ) );
	}

}