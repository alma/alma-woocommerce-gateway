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
	 * @param string $api_key The API key to validate.
	 * @param string $mode The mode of operation (LIVE_MODE or TEST_MODE).
	 *
	 * @return string The merchant ID if the API key is valid, or an empty string if it is not valid.
	 */
	public function check_authentication( string $api_key, string $mode = ClientConfiguration::LIVE_MODE ): string {

		try {
			$client_configuration = new ClientConfiguration( $api_key, $mode );
			$curl_client          = new CurlClient( $client_configuration );
			$merchant_endpoint    = new MerchantEndpoint( $curl_client );
			$merchant             = $merchant_endpoint->me();
		} catch ( ClientConfigurationException | ClientException | MerchantEndpointException $e ) {

			return '';
		}

		return $merchant->id;
	}
}
