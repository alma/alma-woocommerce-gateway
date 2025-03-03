<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\Client;
use Alma\API\RequestError;

class EligibilityService {

	/**
	 * @var Client
	 */
	private $client = null;

	public function __construct( Client $client ) {
		$this->client = $client;
	}

	/**
	 * @throws RequestError
	 */
	public function is_eligible( $eligibility_data ) {
		return $this->client->payments->eligibility( $eligibility_data );
	}
}
