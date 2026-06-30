<?php

namespace Alma\Gateway\Application\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\ClientConfiguration;
use Alma\Client\Application\CurlClient;
use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Client\Application\Response;
use Alma\Client\Domain\ValueObject\Environment;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Psr\Log\LoggerInterface;

class AuthenticationService {

	/** @var LoggerInterface */
	private LoggerInterface $loggerService;

	/**
	 * AuthenticationService constructor.
	 *
	 * @param LoggerService $loggerService
	 */
	public function __construct( LoggerService $loggerService ) {
		$this->loggerService = $loggerService ;
	}

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
			// The "key not valid" message shown to the merchant is a catch-all: this
			// exception is raised for any failure of MerchantEndpoint::me() (transport
			// error, HTTP >= 400, ...), not only an actually invalid key. Log the real
			// cause so a failed validation is diagnosable, e.g. an HTTP 401 (wrong key)
			// versus a cURL connectivity/TLS error from the merchant's server (see
			// ECOM-4278, where the key was valid but the server could not reach the API).
			$statusCode = $e->response instanceof Response ? $e->response->getStatusCode() : 0;
			$this->loggerService->error(
				sprintf(
					'Alma API key validation failed (mode: %s, HTTP status: %d): %s',
					$mode->getMode(),
					$statusCode,
					$e->getMessage()
				),
				array( 'exception' => $e )
			);

			return '';
		}

		return $merchant->getId();
	}
}
