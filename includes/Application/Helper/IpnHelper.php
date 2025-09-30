<?php

namespace Alma\Gateway\Application\Helper;


use Alma\API\Domain\Helper\IpnHelperInterface;
use Alma\API\Infrastructure\Helper\RequestHelper;
use Alma\Gateway\Application\Exception\Service\IpnServiceException;
use Alma\Gateway\Application\Service\IpnService;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Helper\AjaxHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Plugin;

class IpnHelper implements IpnHelperInterface {

	public const CUSTOMER_RETURN = 'alma_customer_return';
	public const IPN_CALLBACK = 'alma_ipn_callback';

	/**
	 * @throws ContainerServiceException
	 */
	public function configureIpnCallback() {
		EventHelper::addEvent(
			'woocommerce_api_' . self::IPN_CALLBACK,
			array( Plugin::get_container()->get( IpnService::class ), 'handleIpnCallback' )
		);
	}

	/**
	 * @throws ContainerServiceException
	 */
	public function configureCustomerReturn() {
		EventHelper::addEvent(
			'woocommerce_api_' . self::CUSTOMER_RETURN,
			array( Plugin::get_container()->get( IpnService::class ), 'handleCustomerReturn' )
		);
	}

	/**
	 * Validate the given signature
	 *
	 * @param string $paymentId The payment ID associated with the IPN
	 * @param string $apiKey The API key used to generate the HMAC signature
	 * @param string $signature The HMAC signature to validate
	 *
	 * @throws IpnServiceException
	 */
	public function validateIpnSignature( string $paymentId, string $apiKey, string $signature ): void {
		if ( empty( $paymentId ) || empty( $apiKey ) || empty( $signature ) ) {
			throw new IpnServiceException( '[ALMA] Missing required parameters' );
		}
		if ( ! RequestHelper::isHmacValidated( $paymentId, $apiKey, $signature ) ) {
			throw new IpnServiceException( '[ALMA] Invalid signature' );
		}
	}

	/**
	 * Send a parameter error response.
	 *
	 * This function is used to handle cases where required parameters are missing
	 * from the request. It sends a standardized bad request response using the
	 * AjaxHelper.
	 *
	 * @param string $customMessage The custom message to include in the bad request response.
	 */
	public function parameterError( string $customMessage = 'Payment validation error: no ID provided.' ): void {
		AjaxHelper::sendBadRequestResponse( $customMessage );
	}

	/**
	 * Send a signature not exist error response.
	 *
	 * This function is used to handle cases where the required signature header
	 * is missing from the request. It sends a standardized unauthorized response
	 * using the AjaxHelper.
	 *
	 * @param string $customMessage The custom message to include in the unauthorized response.
	 */
	public function signatureNotExistError( string $customMessage = 'Header key X-Alma-Signature does not exist.' ): void {
		AjaxHelper::sendUnauthorizedResponse( $customMessage );
	}

	/**
	 * Send an unauthorized response with a custom message.
	 *
	 * This function is used to handle cases where the request is unauthorized
	 * for various reasons. It sends a standardized unauthorized response
	 * using the AjaxHelper with the provided message.
	 *
	 * @param string $customMessage The custom message to include in the unauthorized response.
	 */
	public function unauthorizedError( string $customMessage = 'Unauthorized request.' ): void {
		AjaxHelper::sendUnauthorizedResponse( $customMessage );
	}

	/**
	 * Send a potential fraud error response.
	 *
	 * This function is used to handle cases where potential fraud is detected
	 * during the processing of the request. It sends a standardized bad request
	 * response using the AjaxHelper with the provided message.
	 *
	 * @param string $customMessage The custom message to include in the bad request response.
	 */
	public function potentialFraudError( string $customMessage = 'Potential fraud detected.' ): void {
		AjaxHelper::sendBadRequestResponse( $customMessage );
	}

	/**
	 * Send a success response.
	 *
	 * This function is used to indicate that the request was processed successfully.
	 * It sends a standardized success response using the AjaxHelper.
	 */
	public function success(): void {
		AjaxHelper::sendSuccessResponse();
	}
}
