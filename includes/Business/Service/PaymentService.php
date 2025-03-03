<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\Client;
use Alma\API\ParamsError;
use Alma\API\RequestError;

class PaymentService {

	/**
	 * @var Client
	 */
	private $client = null;

	public function __construct( Client $client ) {
		$this->client = $client;
	}

	/**
	 * @throws ParamsError
	 * @throws RequestError
	 */
	public function create_payment( $payment_data ) {
		return $this->client->payments->create( $payment_data );
	}
}
