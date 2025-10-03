<?php

namespace Alma\Gateway\Tests\Unit\Application\Provider;

use Alma\API\Application\DTO\CustomerDto;
use Alma\API\Application\DTO\OrderDto;
use Alma\API\Application\DTO\PaymentDto;
use Alma\API\Domain\Entity\Payment;
use Alma\API\Infrastructure\Endpoint\PaymentEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\PaymentEndpointException;
use Alma\Gateway\Application\Exception\Service\API\PaymentServiceException;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use PHPUnit\Framework\TestCase;

class PaymentProviderTest extends TestCase {

	private $paymentProvider;
	private $paymentEndpointMock;
	private $loggerServiceMock;


	public function testCreatePaymentApiThrowsException(): void {
		$this->expectException( PaymentServiceException::class );
		$this->expectExceptionMessage( 'Error creating payment: API error' );
		$paymentDto  = $this->createMock( PaymentDto::class );
		$orderDto    = $this->createMock( OrderDto::class );
		$customerDto = $this->createMock( CustomerDto::class );

		$this->paymentEndpointMock->expects( $this->once() )
		                          ->method( 'create' )
		                          ->with( $paymentDto, $orderDto, $customerDto )
		                          ->willThrowException( new PaymentEndpointException( 'API error' ) );

		$this->paymentProvider->createPayment(
			$paymentDto,
			$orderDto,
			$customerDto
		);
	}

	public function testCreatePaymentApiSuccess(): void {
		$paymentMock = $this->createMock( Payment::class );
		$paymentDto  = $this->createMock( PaymentDto::class );
		$orderDto    = $this->createMock( OrderDto::class );
		$customerDto = $this->createMock( CustomerDto::class );
		$this->paymentEndpointMock->expects( $this->once() )
		                          ->method( 'create' )
		                          ->with( $paymentDto, $orderDto, $customerDto )
		                          ->willReturn( $paymentMock );

		$payment = $this->paymentProvider->createPayment(
			$paymentDto,
			$orderDto,
			$customerDto
		);
		$this->assertSame( $paymentMock, $payment );
	}

	public function testFetchPaymentApiThrowsException(): void {
		$this->expectException( PaymentServiceException::class );
		$this->expectExceptionMessage( 'Error fetching payment: API error' );
		$paymentId = 'payment_123';

		$this->paymentEndpointMock->expects( $this->once() )
		                          ->method( 'fetch' )
		                          ->with( $paymentId )
		                          ->willThrowException( new PaymentEndpointException( 'API error' ) );

		$this->paymentProvider->fetchPayment( $paymentId );
	}

	public function testFetchPaymentApiSuccess(): void {
		$paymentMock = $this->createMock( Payment::class );
		$paymentId   = 'payment_123';
		$this->paymentEndpointMock->expects( $this->once() )
		                          ->method( 'fetch' )
		                          ->with( $paymentId )
		                          ->willReturn( $paymentMock );

		$payment = $this->paymentProvider->fetchPayment( $paymentId );
		$this->assertSame( $paymentMock, $payment );
	}

	protected function setUp(): void {
		$this->paymentEndpointMock = $this->createMock( PaymentEndpoint::class );
		$this->loggerServiceMock   = $this->createMock( LoggerService::class );
		$this->paymentProvider     = new PaymentProvider( $this->paymentEndpointMock, $this->loggerServiceMock );
	}

	protected function tearDown(): void {
		$this->paymentEndpointMock = null;
		$this->loggerServiceMock   = null;
		$this->paymentProvider     = null;
	}

}