<?php

namespace Alma\Gateway\Application\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\Client\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;
use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Gateway\Application\Exception\Provider\MerchantProviderException;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Plugin\Application\Port\MerchantProviderInterface;
use Psr\Log\LoggerInterface;

class MerchantProvider implements MerchantProviderInterface, ProviderInterface {

	/** @var MerchantEndpoint $merchantEndpoint */
	private MerchantEndpoint $merchantEndpoint;

	/** @var LoggerInterface $loggerService */
	private LoggerInterface $loggerService;

	/**
	 * MerchantProvider constructor.
	 *
	 * @param MerchantEndpoint $merchantEndpoint The merchant endpoint to use for API calls.
	 * @param LoggerService    $loggerService
	 */
	public function __construct( MerchantEndpoint $merchantEndpoint, LoggerService $loggerService ) {
		$this->merchantEndpoint = $merchantEndpoint;
		$this->loggerService    = $loggerService;
	}

	/**
	 * @param CartInitiatedBusinessEventDto $cartEventData
	 *
	 * @return void
	 * @throws MerchantProviderException
	 */
	public function sendCartInitiatedBusinessEvent( CartInitiatedBusinessEventDto $cartEventData ): void {
		try {
			$this->merchantEndpoint->sendCartInitiatedBusinessEvent( $cartEventData );
		} catch ( MerchantEndpointException $e ) {
			throw new MerchantProviderException( 'Error sending cart initiated business event', 0,
				$e );
		}
	}

	/**
	 * @param OrderConfirmedBusinessEventDto $orderConfirmedBusinessEvent
	 *
	 * @return void
	 * @throws MerchantProviderException
	 */
	public function sendOrderConfirmedBusinessEvent( OrderConfirmedBusinessEventDto $orderConfirmedBusinessEvent ): void {
		try {
			$this->merchantEndpoint->sendOrderConfirmedBusinessEvent( $orderConfirmedBusinessEvent );
		} catch ( MerchantEndpointException $e ) {
			throw new MerchantProviderException( 'Error sending order confirmed business event',
				0, $e );
		}
	}
}
