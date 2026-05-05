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

	public function testSendOrderStatusDirectReturnForNoTransactionID() {
		$almaOrder = $this->createMock( OrderAdapter::class );
		$almaOrder->expects( $this->once() )->method( 'isPaidWithAlma' )->willReturn( true );
		$almaOrder->expects( $this->once() )->method( 'hasATransactionId' )->willReturn( false );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->never() )->method( 'addOrderStatusByMerchantOrderReference' );

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
	

	public function testSendOrderStatusNotCallSendForNonAlmaOrder() {
		$nonAlmaOrderMock = $this->createMock( OrderAdapter::class );
		$nonAlmaOrderMock->expects( $this->once() )->method( 'isPaidWithAlma' )->willReturn( false );
		$nonAlmaOrderMock->expects( $this->never() )->method( 'hasATransactionId' );

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
		$almaOrder->expects( $this->once() )->method( 'hasATransactionId' )->willReturn( true );

		$almaOrder->expects( $this->once() )->method( 'getPaymentId' )->willReturn( 'payment_123' );
		$almaOrder->expects( $this->once() )->method( 'getOrderNumber' )->willReturn( '1' );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->once() )->method( 'addOrderStatusByMerchantOrderReference' )->with(
			'payment_123',
			'1',
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
		$almaOrder->expects( $this->once() )->method( 'hasATransactionId' )->willReturn( true );

		$almaOrder->expects( $this->once() )->method( 'getPaymentId' )->willReturn( 'payment_123' );
		$almaOrder->expects( $this->once() )->method( 'getOrderNumber' )->willReturn( '1' );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->once() )->method( 'addOrderStatusByMerchantOrderReference' )->with(
			'payment_123',
			'1',
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

	/**
	 * Regression test: the merchant order reference sent to Alma when a status changes
	 * MUST be the formatted order number (the same one used at payment creation by
	 * OrderMapper) and NOT the raw post ID. Otherwise plugins that customize the order
	 * number (e.g. Sequential Order Numbers) cause Alma to receive a different reference,
	 * which creates a duplicate order on the payment instead of updating the existing one.
	 */
	public function testSendOrderStatusUsesFormattedOrderNumberAsMerchantReference() {
		$almaOrder = $this->createMock( OrderAdapter::class );
		$almaOrder->expects( $this->once() )->method( 'isPaidWithAlma' )->willReturn( true );
		$almaOrder->expects( $this->once() )->method( 'hasATransactionId' )->willReturn( true );
		$almaOrder->expects( $this->once() )->method( 'getPaymentId' )->willReturn( 'payment_123' );
		$almaOrder->expects( $this->once() )->method( 'getOrderNumber' )->willReturn( 'FR-296288' );

		$paymentProvider = $this->createMock( PaymentProvider::class );
		$paymentProvider->expects( $this->once() )->method( 'addOrderStatusByMerchantOrderReference' )->with(
			'payment_123',
			'FR-296288',
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
		$ref = new \ReflectionProperty( OrderStatusService::class, 'paymentProvider' );
		$ref->setAccessible( true );
		$ref->setValue( $orderStatusService, $paymentProvider );

		$this->assertNull( $orderStatusService->sendOrderStatus( 613799, 'pending', 'completed' ) );
	}

}