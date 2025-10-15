<?php

namespace Alma\Gateway\Application\Provider;

use Alma\API\Application\DTO\CustomerDto;
use Alma\API\Application\DTO\OrderDto;
use Alma\API\Application\DTO\PaymentDto;
use Alma\API\Application\DTO\RefundDto;
use Alma\API\Domain\Entity\Payment;
use Alma\API\Domain\Port\PaymentProviderInterface;
use Alma\API\Infrastructure\Endpoint\PaymentEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\PaymentEndpointException;
use Alma\Gateway\Application\Exception\Service\API\PaymentServiceException;
use Alma\Gateway\Infrastructure\Service\LoggerService;

class PaymentProvider implements PaymentProviderInterface {

	/** @var PaymentEndpoint $paymentEndpoint */
	private PaymentEndpoint $paymentEndpoint;

	/** @var LoggerService $loggerService */
	private LoggerService $loggerService;

	/**
	 * PaymentService constructor.
	 *
	 * @param PaymentEndpoint $paymentEndpoint The payment endpoint to use for API calls.
	 */
	public function __construct( PaymentEndpoint $paymentEndpoint, LoggerService $loggerService ) {
		$this->paymentEndpoint = $paymentEndpoint;
		$this->loggerService   = $loggerService;
	}

	/**
	 * Create a new payment.
	 *
	 * @param PaymentDto  $paymentDto The payment data transfer object.
	 * @param OrderDto    $orderDto The order data transfer object.
	 * @param CustomerDto $customerDto The customer data transfer object.
	 *
	 * @return Payment The created payment.
	 *
	 * @throws PaymentServiceException
	 */
	public function createPayment(
		PaymentDto $paymentDto,
		OrderDto $orderDto,
		CustomerDto $customerDto
	): Payment {
		try {
			return $this->paymentEndpoint->create( $paymentDto, $orderDto, $customerDto );
		} catch ( PaymentEndpointException $e ) {
			throw new PaymentServiceException( 'Error creating payment: ' . $e->getMessage() );
		}
	}

	/**
	 * Fetch a payment by its ID.
	 *
	 * @param string $paymentId The ID of the payment to fetch.
	 *
	 * @return Payment The fetched payment.
	 *
	 * @throws PaymentServiceException
	 */
	//TODO REPLACE INTERFACE REMOVE NULL
	public function fetchPayment( ?string $paymentId ): Payment {
		try {
			return $this->paymentEndpoint->fetch( $paymentId );
		} catch ( PaymentEndpointException $e ) {
			throw new PaymentServiceException( 'Error fetching payment: ' . $e->getMessage() );
		}
	}

	/**
	 * Flag a payment as potential fraud.
	 *
	 * @throws PaymentServiceException
	 */
	public function flagAsFraud( string $id, string $reason ): bool {
		try {
			return $this->paymentEndpoint->flagAsPotentialFraud( $id, $reason );
		} catch ( PaymentEndpointException $e ) {
			throw new PaymentServiceException( 'Error flagging payment as fraud: ' . $e->getMessage() );
		}
	}

	/**
	 * Refund a payment.
	 *
	 * @param string    $paymentId The ID of the payment to refund.
	 * @param RefundDto $refundDto The Refund Data Transfer Object containing the refund details.
	 *
	 * @return bool
	 */
	public function refundPayment( string $paymentId, RefundDto $refundDto ): bool {
		try {
			$this->paymentEndpoint->refund( $paymentId, $refundDto );
		} catch ( PaymentEndpointException $e ) {
			$this->loggerService->debug( $e->getMessage() );

			return false;
		}

		return true;
	}
}
