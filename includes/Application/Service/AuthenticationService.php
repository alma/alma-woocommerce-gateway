<?php

namespace Alma\Gateway\Application\Service;

use Alma\Client\Application\ClientConfiguration;
use Alma\Client\Application\CurlClient;
use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Client\Domain\ValueObject\Environment;

class AuthenticationService {

	/**
	 * Check if the provided API key is valid by making a request to the Merchant endpoint.
	 * We do not use Dependency Injection here because this method is used in the plugin activation process,
	 * where the full dependency injection container is not available.
	 *
	 * @param string      $apiKey The API key to validate.
	 * @param Environment $mode The mode of operation (LIVE_MODE or TEST_MODE).
	 *
	 * @return string The merchant ID if the API key is valid, or an empty string if it is not valid.
	 */
	public function checkAuthentication( string $apiKey, Environment $mode ): string {

		try {
			$clientConfiguration = new ClientConfiguration( $apiKey, $mode );
			$curlClient          = new CurlClient( $clientConfiguration );
			$merchantEndpoint    = new MerchantEndpoint( $curlClient );
			$merchant            = $merchantEndpoint->me();
		} catch ( MerchantEndpointException $e ) {

			return '';
		}

		return $merchant->getId();
	}
}
