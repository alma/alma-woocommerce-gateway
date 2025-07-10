<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\PaymentEndpoint;
use Alma\API\Entities\DTO\CustomerDto;
use Alma\API\Entities\DTO\OrderDto;
use Alma\API\Entities\DTO\PaymentDto;
use Alma\API\Entities\Payment;
use Alma\API\Exceptions\Endpoint\PaymentEndpointException;
use Alma\Gateway\Business\Exception\PaymentServiceException;

class PaymentService {
	const CAPTURE_METHOD_AUTOMATIC = 'automatic';
	const CAPTURE_METHOD_MANUAL    = 'manual';

	/** @var PaymentEndpoint */
	private PaymentEndpoint $payment_endpoint;

	public function __construct( PaymentEndpoint $payment_endpoint ) {
		$this->payment_endpoint = $payment_endpoint;
	}

	/**
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
	 * @throws PaymentServiceException
	 */
	public function flag_as_fraud( string $id, string $reason ): bool {
		try {
			return $this->payment_endpoint->flagAsPotentialFraud( $id, $reason );
		} catch ( PaymentEndpointException $e ) {
			throw new PaymentServiceException( 'Error flagging payment as fraud: ' . $e->getMessage() );
		}
	}
}
