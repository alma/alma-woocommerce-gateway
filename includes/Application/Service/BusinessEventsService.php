<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\MerchantEndpointException;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Infrastructure\Helper\CartHelper;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;

class BusinessEventsService
{
	const ALMA_BUSINESS_EVENT_TABLE = 'alma_business_data';
	const ALMA_CART_ID       = 'alma_cart_id';
	private SessionHelper $session;
	private BusinessEventsRepository $businessEventsRepository;
	private MerchantEndpoint $merchantEndpoint;

	public function __construct(
		SessionHelper $session,
		BusinessEventsRepository $businessEventsRepository,
		MerchantEndpoint $merchantEndpoint
	) {
		$this->session = $session;
		$this->businessEventsRepository = $businessEventsRepository;
		$this->merchantEndpoint = $merchantEndpoint;
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function onCartInitiated(): void {
		$alma_cart_id = $this->getCartId();
		if ( ! $this->businessEventsRepository->isCartIdValid( $alma_cart_id ) ) {
			$this->businessEventsRepository->saveCartId( $alma_cart_id );
			$this->session->setSession( self::ALMA_CART_ID, $alma_cart_id );
			try {
				$cartInitiated = new CartInitiatedBusinessEventDto( $alma_cart_id );
				$this->merchantEndpoint->sendCartInitiatedBusinessEvent( $cartInitiated );
			} catch ( ParametersException $e ) {
				throw new BusinessEventsServiceException( 'Failed to create CartInitiatedBusinessEventDto: ' . $e->getMessage() );
			} catch ( MerchantEndpointException $e ) {
				throw new BusinessEventsServiceException( 'Error sending cart initiated business event: ' . $e->getMessage() );
			}
		}
	}

	protected function getCartId(): int {
		$alma_cart_id = $this->session->getSession( self::ALMA_CART_ID, null );
		if ( empty($alma_cart_id) ) {
			$alma_cart_id = CartHelper::generateUniqueCartId();
		}

		return $alma_cart_id;
	}
}
