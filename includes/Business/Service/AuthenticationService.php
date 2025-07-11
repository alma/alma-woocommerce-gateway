<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\ClientConfiguration;
use Alma\API\CurlClient;
use Alma\API\Endpoint\MerchantEndpoint;
use Alma\API\Exceptions\ClientConfigurationException;
use Alma\API\Exceptions\ClientException;
use Alma\API\Exceptions\Endpoint\MerchantEndpointException;

class AuthenticationService {

	/**
	 * Check if the provided API key is valid by making a request to the Merchant endpoint.
	 * We do not use Dependency Injection here because this method is used in the plugin activation process,
	 * where the full dependency injection container is not available.
	 *
	 * @param string $api_key The API key to validate.
	 * @param string $mode The mode of operation (LIVE_MODE or TEST_MODE).
	 *
	 * @return bool True if the API key is valid, false otherwise.
	 */
	public function check_authentication( string $api_key, string $mode = ClientConfiguration::LIVE_MODE ): bool {

		try {
			$client_configuration = new ClientConfiguration( $api_key, $mode );
			$curl_client          = new CurlClient( $client_configuration );
			$merchant_endpoint    = new MerchantEndpoint( $curl_client );
			$merchant_endpoint->me();
		} catch ( ClientConfigurationException | ClientException | MerchantEndpointException $e ) {

			return false;
		}

		return true;
	}
}
