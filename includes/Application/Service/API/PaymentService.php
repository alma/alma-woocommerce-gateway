<?php

namespace Alma\Gateway\Application\Service\API;

use Alma\API\DTO\CustomerDto;
use Alma\API\DTO\OrderDto;
use Alma\API\DTO\PaymentDto;
use Alma\API\DTO\RefundDto;
use Alma\API\Endpoint\PaymentEndpoint;
use Alma\API\Entity\Payment;
use Alma\API\Exception\Endpoint\PaymentEndpointException;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Exception\PaymentServiceException;
use Alma\Gateway\Application\Service\LoggerService;

class PaymentService {
	const CAPTURE_METHOD_AUTOMATIC = 'automatic';
	const CAPTURE_METHOD_MANUAL    = 'manual';

	/** @var PaymentEndpoint */
	private PaymentEndpoint $payment_endpoint;

	/** @var LoggerService $logger_service */
	private LoggerService $logger_service;

	/**
	 * PaymentService constructor.
	 *
	 * @param PaymentEndpoint $payment_endpoint The payment endpoint to use for API calls.
	 */
	public function __construct( PaymentEndpoint $payment_endpoint, LoggerService $logger_service ) {
		$this->payment_endpoint = $payment_endpoint;
		$this->logger_service   = $logger_service;
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
	public function create_payment(
		PaymentDto $payment_dto,
		OrderDto $order_dto,
		CustomerDto $customer_dto
	): Payment {
		try {
			return $this->payment_endpoint->create( $payment_dto, $order_dto, $customer_dto );
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
	public function fetch_payment( ?string $payment_id ): Payment {
		try {
			return $this->payment_endpoint->fetch( $payment_id );
		} catch ( PaymentEndpointException $e ) {
			throw new PaymentServiceException( 'Error fetching payment: ' . $e->getMessage() );
		}
	}

	/**
	 * Flag a payment as potential fraud.
	 *
	 * @throws PaymentServiceException|PaymentEndpointException
	 */
	public function flag_as_fraud( string $id, string $reason ): bool {
		try {
			return $this->payment_endpoint->flagAsPotentialFraud( $id, $reason );
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
	 * @throws PaymentEndpointException
	 */
	public function refund_payment( string $paymentId, RefundDto $refundDto ): bool {
		try {
			$this->payment_endpoint->refund( $paymentId, $refundDto );
		} catch ( PaymentEndpointException | ParametersException $e ) {
			$this->logger_service->debug( $e->getMessage() );

			return false;
		}

		return true;
	}
}
