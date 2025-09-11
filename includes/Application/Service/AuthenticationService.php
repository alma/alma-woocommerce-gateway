<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\ClientConfiguration;
use Alma\API\CurlClient;
use Alma\API\Endpoint\MerchantEndpoint;
use Alma\API\Exception\ClientConfigurationException;
use Alma\API\Exception\ClientException;
use Alma\API\Exception\Endpoint\MerchantEndpointException;

class AuthenticationService {

	/**
	 * Check if the provided API key is valid by making a request to the Merchant endpoint.
	 * We do not use Dependency Injection here because this method is used in the plugin activation process,
	 * where the full dependency injection container is not available.
	 *
	 * @param string $apiKey The API key to validate.
	 * @param string $mode The mode of operation (LIVE_MODE or TEST_MODE).
	 *
	 * @return string The merchant ID if the API key is valid, or an empty string if it is not valid.
	 */
	public function checkAuthentication( string $apiKey, string $mode = ClientConfiguration::LIVE_MODE ): string {

		try {
			$clientConfiguration = new ClientConfiguration( $apiKey, $mode );
			$curlClient          = new CurlClient( $clientConfiguration );
			$merchantEndpoint    = new MerchantEndpoint( $curlClient );
			$merchant            = $merchantEndpoint->me();
		} catch ( ClientConfigurationException|ClientException|MerchantEndpointException $e ) {

			return '';
		}

		return $merchant->id;
	}
}
