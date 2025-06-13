<?php

namespace Alma\Gateway\Business\Service\API;

use Alma\API\Endpoint\PaymentEndpoint;
use Alma\API\Entities\Payment;
use Alma\API\Exceptions\PaymentServiceException;

class PaymentService {

	/** @var PaymentEndpoint */
	private PaymentEndpoint $payment_endpoint;

	public function __construct( PaymentEndpoint $payment_endpoint ) {
		$this->payment_endpoint = $payment_endpoint;
	}

	/**
	 * @throws PaymentServiceException
	 */
	public function create_payment( int $purchase_amount, array $additional_data = array() ): Payment {
		return $this->payment_endpoint->create( $purchase_amount, $additional_data );
	}
}
