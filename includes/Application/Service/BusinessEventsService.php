<?php

namespace Alma\Gateway\Application\Service;

use Alma\Client\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\Client\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;
use Alma\Client\Application\Exception\ParametersException;
use Alma\Client\Domain\Entity\EligibilityList;
use Alma\Gateway\Application\Exception\Provider\MerchantProviderException;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Provider\MerchantProviderAwareTrait;
use Alma\Gateway\Application\Provider\MerchantProviderFactory;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Helper\CartHelper;
use Alma\Gateway\Infrastructure\Helper\OrderHelper;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use WC_Order;

class BusinessEventsService {
	use MerchantProviderAwareTrait;

	const ALMA_BUSINESS_EVENT_TABLE = 'alma_business_data';
	const ALMA_CART_ID = 'alma_cart_id';
	private SessionHelper $sessionHelper;
	private BusinessEventsRepository $businessEventsRepository;

	public function __construct(
		SessionHelper $sessionHelper,
		BusinessEventsRepository $businessEventsRepository,
		MerchantProviderFactory $merchantProviderFactory
	) {
		$this->sessionHelper            = $sessionHelper;
		$this->businessEventsRepository = $businessEventsRepository;
		$this->merchantProviderFactory  = $merchantProviderFactory;
	}

	/**
	 * When a product is added to the cart we create a row in the business events table if not exists
	 * And we send the cart_initiated business event to Alma if not already sent for this cart ID
	 * @throws BusinessEventsServiceException
	 */
	public function onCartInitiated(): void {
		$this->getMerchantProvider();
		$almaCartId = $this->sessionCartId();
		if ( $this->alreadyConverted( $almaCartId ) ) {
			$almaCartId = $this->resetSessionCartId();
		}
		if ( ! $this->alreadyExist( $almaCartId ) ) {
			$this->businessEventsRepository->saveCartId( $almaCartId );
			try {
				$cartInitiated = new CartInitiatedBusinessEventDto( $almaCartId );
				$this->merchantProvider->sendCartInitiatedBusinessEvent( $cartInitiated );
			} catch ( ParametersException $e ) {
				throw new BusinessEventsServiceException( 'Failed to create CartInitiatedBusinessEventDto',
					0, $e );
			} catch ( MerchantProviderException $e ) {
				throw new BusinessEventsServiceException( 'Error sending cart initiated business event',
					0, $e );
			}
		}
	}

	/**
	 * When an order status changed we send the order_confirmed business event to Alma
	 *
	 * @param string       $oldStatus
	 * @param string       $newStatus
	 * @param OrderAdapter $order
	 *
	 * @return void
	 * @throws BusinessEventsServiceException
	 */
	public function onOrderConfirmed( string $oldStatus, string $newStatus, OrderAdapter $order ): void {
		$isPayNow  = false;
		$isBnpl    = false;
		$paymentId = '';
		if ( 'pending' === $oldStatus && in_array( $newStatus,
				array_merge( OrderHelper::wcGetIsPaidStatuses(), array( 'on-hold' ) ) ) ) {
			$almaBusinessData = $this->businessEventsRepository->getRowByOrderId( $order->getId() );
			if ( strpos( $order->getPaymentMethod(), 'alma' ) !== false ) {
				$isPayNow  = $order->getPaymentMethod() === sprintf(
						AbstractGateway::NAME_ALMA_GATEWAYS, PayNowGateway::PAYMENT_METHOD
					);
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
				throw new BusinessEventsServiceException( 'Failed to create OrderConfirmedBusinessEventDto',
					0, $e );
			} catch ( MerchantProviderException $e ) {
				throw new BusinessEventsServiceException( 'Error sending order confirmed business event',
					0, $e );
			}
		}
	}

	/**
	 * Save the order ID when the order is created on classic checkout
	 *
	 * @param int $orderId
	 *
	 * @return void
	 */
	public function onCreateOrder( int $orderId ): void {
		$almaCartId = $this->sessionCartId();
		$this->businessEventsRepository->saveOrderId( $almaCartId, $orderId );
	}

	/**
	 * Save the order ID when the order is created from the block
	 *
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function onCreateOrderBlock( WC_Order $order ): void {
		$almaCartId = $this->sessionCartId();
		$this->businessEventsRepository->saveOrderId( $almaCartId, $order->get_id() );
	}

	/**
	 * @param string $almaPaymentId
	 *
	 * @return void
	 */
	public function saveAlmaPaymentId( string $almaPaymentId ): void {
		$almaCartId = $this->sessionCartId();
		$this->businessEventsRepository->saveAlmaPaymentId( $almaCartId, $almaPaymentId );
	}

	/**
	 * @param EligibilityList $eligibilityList
	 *
	 * @return void
	 */
	public function updateEligibility( EligibilityList $eligibilityList ): void {
		$isEligible = false;
		foreach ( $eligibilityList as $eligibility ) {
			if ( $eligibility->isEligible() ) {
				$isEligible = true;
				break;
			}
		}
		$this->businessEventsRepository->saveEligibility( $this->sessionCartId(), $isEligible );
	}

	/**
	 * @return int
	 */
	protected function sessionCartId(): int {
		$almaCartId = $this->sessionHelper->getSession( self::ALMA_CART_ID );
		if ( empty( $almaCartId ) ) {
			$almaCartId = CartHelper::generateUniqueCartId();
			$this->sessionHelper->setSession( self::ALMA_CART_ID, $almaCartId );
		}

		return $almaCartId;
	}

	/**
	 * Check if cart ID already exists in the business events table.
	 *
	 * @param $almaCartId
	 *
	 * @return bool
	 */
	protected function alreadyExist( $almaCartId ): bool {
		$result = $this->businessEventsRepository->getCartRowIfExist( $almaCartId );

		return $result !== null;
	}

	/**
	 * Check if cart ID already converted in the business events table.
	 *
	 * @param $almaCartId
	 *
	 * @return bool
	 */
	protected function alreadyConverted( $almaCartId ): bool {
		$result = $this->businessEventsRepository->getCartRowIfExist( $almaCartId );

		return $result !== null && $result->order_id !== null;
	}

	/**
	 * Reset cart ID if already converted to order.
	 * @return int
	 */
	protected function resetSessionCartId(): int {
		$this->sessionHelper->unsetKeySession( self::ALMA_CART_ID );
		$almaCartId = CartHelper::generateUniqueCartId();
		$this->sessionHelper->setSession( self::ALMA_CART_ID, $almaCartId );

		return $almaCartId;
	}
}
