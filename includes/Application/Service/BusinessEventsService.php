<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\MerchantServiceException;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Provider\MerchantProviderAwareTrait;
use Alma\Gateway\Application\Provider\MerchantProviderFactory;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Helper\CartHelper;
use Alma\Gateway\Infrastructure\Helper\OrderHelper;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use Automattic\WooCommerce\Admin\Overrides\Order;

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
		if ($this->businessEventsRepository->alreadyExist( $almaCartId ) && $this->businessEventsRepository->alreadyConverted( $almaCartId )) {
			$almaCartId = $this->resetCartId();
		}
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

	/**
	 * @param string $oldStatus
	 * @param string $newStatus
	 * @param OrderAdapter $order
	 *
	 * @return void
	 * @throws BusinessEventsServiceException
	 */
	public function onOrderConfirmed(string $oldStatus, string $newStatus, OrderAdapter $order): void {
		$isPayNow = false;
		$isBnpl    = false;
		$paymentId = '';
		if ( 'pending' === $oldStatus && in_array( $newStatus, array_merge( OrderHelper::wcGetIsPaidStatuses(), array( 'on-hold' ) ) ) ) {
			$almaBusinessData = $this->businessEventsRepository->getRowByOrderId($order->getId());
			if ( strpos( $order->getPaymentMethod(), 'alma' ) !== false ) {
				$isPayNow = $order->getPaymentMethod() === 'alma_' . PayNowGateway::PAYMENT_METHOD . '_gateway';
				$isBnpl    = ! $isPayNow;
				$paymentId = $almaBusinessData->alma_payment_id;
			}

			try {
				$this->getMerchantProvider();
				$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
					$isPayNow,
					$isBnpl,
					(bool) $almaBusinessData->is_bnpl_eligible,
					(string) $order->getId(),
					$almaBusinessData->cart_id,
					$paymentId
				);
				$this->merchantProvider->sendOrderConfirmedBusinessEvent( $orderConfirmedBusinessEvent );
			} catch ( ParametersException $e ) {
				throw new BusinessEventsServiceException( 'Failed to create OrderConfirmedBusinessEventDto: ' . $e->getMessage() );
			} catch ( MerchantServiceException $e ) {
				throw new BusinessEventsServiceException( 'Error sending order confirmed business event: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * @param int $orderId
	 *
	 * @return void
	 */
	public function onCreateOrder(int $orderId): void {
		$almaCartId = $this->getCartId();
		$this->businessEventsRepository->updateOrderId($almaCartId, $orderId);
	}

	/**
	 * @param string $almaPaymentId
	 *
	 * @return void
	 */
	public function saveAlmaPaymentId(string $almaPaymentId): void {
		$almaCartId = $this->getCartId();
		$this->businessEventsRepository->saveAlmaPaymentId($almaCartId, $almaPaymentId);
	}

	/**
	 * @param EligibilityList $eligibilityList
	 *
	 * @return void
	 */
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

	/**
	 * @return int
	 */
	protected function getCartId(): int {
		$almaCartId = $this->sessionHelper->getSession( self::ALMA_CART_ID );
		if ( empty($almaCartId) ) {
			$almaCartId = CartHelper::generateUniqueCartId();
			$this->sessionHelper->setSession( self::ALMA_CART_ID, $almaCartId );
		}

		return $almaCartId;
	}

	/**
	 * Reset cart ID if already converted to order.
	 * @return int
	 */
	protected function resetCartId(): int {
		$this->sessionHelper->unsetSession( self::ALMA_CART_ID );
		$almaCartId =  CartHelper::generateUniqueCartId();
		$this->sessionHelper->setSession( self::ALMA_CART_ID, $almaCartId );

		return $almaCartId;
	}
}
