<?php

namespace Alma\Gateway\Tests\Unit\Application\Provider;

use Alma\Client\Application\DTO\CustomerDto;
use Alma\Client\Application\DTO\OrderDto;
use Alma\Client\Application\DTO\PaymentDto;
use Alma\Client\Application\Endpoint\PaymentEndpoint;
use Alma\Client\Application\Exception\Endpoint\PaymentEndpointException;
use Alma\Client\Domain\Entity\Payment;
use Alma\Gateway\Application\Exception\Provider\PaymentProviderException;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use PHPUnit\Framework\TestCase;

class PaymentProviderTest extends TestCase {

	private $paymentProvider;
	private $paymentEndpointMock;
	private $loggerServiceMock;


	public function testCreatePaymentApiThrowsException(): void {
		$this->expectException( PaymentProviderException::class );
		$this->expectExceptionMessage( 'Error creating payment' );
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

	/**
	 * @throws PaymentProviderException
	 */
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
		$this->expectException( PaymentProviderException::class );
		$this->expectExceptionMessage( 'Error fetching payment' );
		$paymentId = 'payment_123';

		$this->paymentEndpointMock->expects( $this->once() )
		                          ->method( 'fetch' )
		                          ->with( $paymentId )
		                          ->willThrowException( new PaymentEndpointException( 'API error' ) );

		$this->paymentProvider->fetchPayment( $paymentId );
	}

	/**
	 * @throws PaymentProviderException
	 */
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
