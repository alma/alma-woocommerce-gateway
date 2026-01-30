<?php

namespace Alma\Gateway\Application\Provider;

use Alma\Client\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\Client\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;
use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Gateway\Application\Exception\Service\API\MerchantServiceException;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Plugin\Application\Port\MerchantProviderInterface;

class MerchantProvider implements MerchantProviderInterface, ProviderInterface {

	/** @var MerchantEndpoint $merchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var LoggerService $loggerService */
	private LoggerService $loggerService;

	/**
	 * MerchantProvider constructor.
	 *
	 * @param MerchantEndpoint $merchantEndpoint The merchant endpoint to use for API calls.
	 */
	public function __construct( MerchantEndpoint $merchantEndpoint, LoggerService $loggerService ) {
		$this->merchantEndpoint = $merchantEndpoint;
		$this->loggerService    = $loggerService;
	}

	/**
	 * @param CartInitiatedBusinessEventDto $cartEventData
	 *
	 * @return void
	 * @throws MerchantServiceException
	 */
	public function sendCartInitiatedBusinessEvent( CartInitiatedBusinessEventDto $cartEventData ): void {
		try {
			$this->merchantEndpoint->sendCartInitiatedBusinessEvent( $cartEventData );
		} catch ( MerchantEndpointException $e ) {
			throw new MerchantServiceException( 'Error sending cart initiated business event: ' . $e->getMessage() );
		}
	}

	/**
	 * @param OrderConfirmedBusinessEventDto $orderConfirmedBusinessEvent
	 *
	 * @return void
	 * @throws MerchantServiceException
	 */
	public function sendOrderConfirmedBusinessEvent( OrderConfirmedBusinessEventDto $orderConfirmedBusinessEvent ): void {
		try {
			$this->merchantEndpoint->sendOrderConfirmedBusinessEvent( $orderConfirmedBusinessEvent );
		} catch ( MerchantEndpointException $e ) {
			throw new MerchantServiceException( 'Error sending order confirmed business event: ' . $e->getMessage() );
		}
	}
}
