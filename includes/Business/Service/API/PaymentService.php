<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\PaymentEndpoint;
use Alma\API\Entities\DTO\CustomerDto;
use Alma\API\Entities\DTO\OrderDto;
use Alma\API\Entities\DTO\PaymentDto;
use Alma\API\Entities\Payment;
use Alma\API\Exceptions\Endpoint\PaymentEndpointException;
use Alma\API\Exceptions\ParametersException;
use Alma\Gateway\Business\Exception\PaymentServiceException;
use Alma\Gateway\Business\Helper\DisplayHelper;
use Alma\Gateway\Business\Service\LoggerService;

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
	 * @throws PaymentServiceException
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
	 * @throws PaymentServiceException
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
	 * @param string $payment_id The ID of the payment to refund.
	 * @param string $merchant_reference The merchant reference for the refund (order ID)
	 * @param float  $amount The amount to refund.
	 * @param bool   $partial Whether the refund is partial or full.
	 * @param string $reason The reason for the refund (optional).
	 *
	 * @return bool
	 * @todo Implements refunds by hooks like in v5.x? @ see includes/Services/RefundService.php
	 */
	public function refund_payment( string $payment_id, string $merchant_reference, float $amount, bool $partial, string $reason = '' ): bool {
		try {
			if ( $partial ) {
				// Partial refund
				$this->payment_endpoint->partialRefund(
					$payment_id,
					DisplayHelper::price_to_cent( $amount ),
					$merchant_reference,
					$reason
				);
				$this->logger_service->debug( 'Partial response' );

			} else {
				// Full refund
				$this->payment_endpoint->fullRefund( $payment_id, $merchant_reference, $reason );
				$this->logger_service->debug( 'Full response' );
			}
		} catch ( PaymentEndpointException | ParametersException $e ) {
			$this->logger_service->debug( $e->getMessage() );

			return false;
		}

		return true;
	}
}
