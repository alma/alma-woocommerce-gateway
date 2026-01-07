<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\MerchantServiceException;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Provider\MerchantProviderAwareTrait;
use Alma\Gateway\Application\Provider\MerchantProviderFactory;
use Alma\Gateway\Infrastructure\Helper\CartHelper;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;

class BusinessEventsService
{
	use MerchantProviderAwareTrait;

	const ALMA_BUSINESS_EVENT_TABLE = 'alma_business_data';
	const ALMA_CART_ID       = 'alma_cart_id';
	private SessionHelper $sessionHelper;
	private BusinessEventsRepository $businessEventsRepository;

	public function __construct(
		SessionHelper $sessionHelper,
		BusinessEventsRepository $businessEventsRepository,
		MerchantProviderFactory $merchantProviderFactory
	) {
		$this->sessionHelper            = $sessionHelper;
		$this->businessEventsRepository = $businessEventsRepository;
		$this->merchantProviderFactory = $merchantProviderFactory;
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function onCartInitiated(): void {
		$this->getMerchantProvider();
		$almaCartId = $this->getCartId();
		if ( ! $this->businessEventsRepository->alreadyExist( $almaCartId ) ) {
			$this->businessEventsRepository->saveCartId( $almaCartId );
			try {
				$cartInitiated = new CartInitiatedBusinessEventDto( $almaCartId );
				$this->merchantProvider->sendCartInitiatedBusinessEvent( $cartInitiated );
			} catch ( ParametersException $e ) {
				throw new BusinessEventsServiceException( 'Failed to create CartInitiatedBusinessEventDto: ' . $e->getMessage() );
			} catch ( MerchantServiceException $e ) {
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
