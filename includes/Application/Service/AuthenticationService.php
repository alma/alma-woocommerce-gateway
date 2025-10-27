<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\ValueObject\Environment;
use Alma\API\Infrastructure\ClientConfiguration;
use Alma\API\Infrastructure\CurlClient;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\MerchantEndpointException;

class AuthenticationService {

	/**
	 * Check if the provided API key is valid by making a request to the Merchant endpoint.
	 * We do not use Dependency Injection here because this method is used in the plugin activation process,
	 * where the full dependency injection container is not available.
	 *
	 * @param string      $apiKey The API key to validate.
	 * @param Environment $environment The environment (test or live) to use for the validation.
	 *
	 * @return string The merchant ID if the API key is valid, or an empty string if it is not valid.
	 */
	public function checkAuthentication( string $apiKey, Environment $environment ): string {
		try {
			$clientConfiguration = new ClientConfiguration( $apiKey, $environment );
			$curlClient          = new CurlClient( $clientConfiguration );
			$merchantEndpoint    = new MerchantEndpoint( $curlClient );
			$merchant            = $merchantEndpoint->me();
		} catch ( MerchantEndpointException $e ) {

			return '';
		}

		return $merchant->getId();
	}
}
