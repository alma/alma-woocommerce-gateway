<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Provider\PaymentProviderAwareTrait;
use Alma\Gateway\Application\Provider\PaymentProviderFactory;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Plugin\Infrastructure\Repository\OrderRepositoryInterface;

class OrderStatusService {

	/** Add ability to use PaymentProviderFactory */
	use PaymentProviderAwareTrait;


	private OrderRepositoryInterface $orderRepository;

	public function __construct(
		PaymentProviderFactory $paymentProviderFactory,
		OrderRepositoryInterface $orderRepository
	) {
		$this->paymentProviderFactory = $paymentProviderFactory;
		$this->orderRepository        = $orderRepository;
	}

	public function initSendOrderStatusHook() {
		EventHelper::addEvent( 'woocommerce_order_status_changed', [ $this, 'sendOrderStatus' ], 11, 3 );
	}


	public function sendOrderStatus( int $orderId, string $oldStatus, string $newStatus ): void {

		// Get the order
		try {
			$order = $this->orderRepository->getById( $orderId );
		} catch ( ProductRepositoryException $exception ) {
			return;
		}

		// Check if the order is paid with Alma and has a transaction ID, if not return
		if ( ! $order->isPaidWithAlma() || ! $order->hasATransactionId() ) {
			return;
		}
		// Get the payment ID associated with the order ID
		$paymentId = $order->getPaymentId();
		$isShipped = $this->getShipmentByStatus( $newStatus );

		$this->getPaymentProvider();
		$this->paymentProvider->addOrderStatusByMerchantOrderReference( $paymentId, $orderId, $newStatus, $isShipped );

	}

	/**
	 * Check if the order is shipped.
	 *
	 * @param string $status Order status.
	 *
	 * @return bool | null
	 */
	private function getShipmentByStatus( $status ) {
		switch ( $status ) {
			case 'pending':
			case 'on-hold':
			case 'processing':
			case 'failed':
			case 'refunded':
			case 'cancelled':
			case 'checkout-draft':
				return false;
			case 'completed':
				return true;
			default:
				return null;
		}
	}
}
