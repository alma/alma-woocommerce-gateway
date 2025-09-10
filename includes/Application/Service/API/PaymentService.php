<?php

namespace Alma\Gateway\Application\Service\API;

use Alma\API\Domain\Exception\PaymentServiceException;
use Alma\API\Domain\Service\API\PaymentServiceInterface;
use Alma\API\DTO\CustomerDto;
use Alma\API\DTO\OrderDto;
use Alma\API\DTO\PaymentDto;
use Alma\API\DTO\RefundDto;
use Alma\API\Endpoint\PaymentEndpoint;
use Alma\API\Entity\Payment;
use Alma\API\Exception\Endpoint\PaymentEndpointException;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Service\LoggerService;

class PaymentService implements PaymentServiceInterface {
	const CAPTURE_METHOD_AUTOMATIC = 'automatic';
	const CAPTURE_METHOD_MANUAL = 'manual';

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
	 * @param PaymentDto  $payment_dto The payment data transfer object.
	 * @param OrderDto    $order_dto The order data transfer object.
	 * @param CustomerDto $customer_dto The customer data transfer object.
	 *
	 * @return Payment The created payment.
	 *
	 * @throws PaymentServiceException
	 */
	public function createPayment(
		PaymentDto $payment_dto,
		OrderDto $order_dto,
		CustomerDto $customer_dto
	): Payment {
		try {
			return $this->paymentEndpoint->create( $payment_dto, $order_dto, $customer_dto );
		} catch ( PaymentEndpointException $e ) {
			throw new PaymentServiceException( 'Error creating payment: ' . $e->getMessage() );
		}
	}

	/**
	 * Fetch a payment by its ID.
	 *
	 * @param string|null $payment_id The ID of the payment to fetch.
	 *
	 * @throws PaymentServiceException|PaymentEndpointException
	 */
	public function fetchPayment( ?string $payment_id ): Payment {
		try {
			return $this->paymentEndpoint->fetch( $payment_id );
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
		} catch ( PaymentEndpointException|ParametersException $e ) {
			$this->loggerService->debug( $e->getMessage() );

			return false;
		}

		return true;
	}
}
