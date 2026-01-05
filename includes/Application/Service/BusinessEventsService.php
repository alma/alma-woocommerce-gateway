<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Domain\Entity\EligibilityList;
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
	private SessionHelper $sessionHelper;
	private BusinessEventsRepository $businessEventsRepository;
	private MerchantEndpoint $merchantEndpoint;

	public function __construct(
		SessionHelper $sessionHelper,
		BusinessEventsRepository $businessEventsRepository,
		MerchantEndpoint $merchantEndpoint
	) {
		$this->sessionHelper            = $sessionHelper;
		$this->businessEventsRepository = $businessEventsRepository;
		$this->merchantEndpoint = $merchantEndpoint;
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function onCartInitiated(): void {
		$almaCartId = $this->getCartId();
		if ( ! $this->businessEventsRepository->alreadyExist( $almaCartId ) ) {
			$this->businessEventsRepository->saveCartId( $almaCartId );
			try {
				$cartInitiated = new CartInitiatedBusinessEventDto( $almaCartId );
				$this->merchantEndpoint->sendCartInitiatedBusinessEvent( $cartInitiated );
			} catch ( ParametersException $e ) {
				throw new BusinessEventsServiceException( 'Failed to create CartInitiatedBusinessEventDto: ' . $e->getMessage() );
			} catch ( MerchantEndpointException $e ) {
				throw new BusinessEventsServiceException( 'Error sending cart initiated business event: ' . $e->getMessage() );
			}
		}
	}

	public function updateEligibility(EligibilityList $eligibilityList): void {
		$isEligible = false;
		foreach ( $eligibilityList as $eligibility ) {
			if ( $eligibility->isEligible() ) {
				$isEligible = true;
				break;
			}
		}
		$this->businessEventsRepository->saveEligibility($this->getCartId(), $isEligible);
	}

	protected function getCartId(): int {
		$alma_cart_id = $this->sessionHelper->getSession( self::ALMA_CART_ID );
		if ( empty($alma_cart_id) ) {
			$alma_cart_id = CartHelper::generateUniqueCartId();
			$this->session->setSession( self::ALMA_CART_ID, $alma_cart_id );
		}

		return $alma_cart_id;
	}
}
