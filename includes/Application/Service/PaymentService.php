<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Entity\Payment;
use Alma\Gateway\Application\Exception\Service\API\PaymentServiceException;
use Alma\Gateway\Application\Mapper\CustomerMapper;
use Alma\Gateway\Application\Mapper\OrderMapper;
use Alma\Gateway\Application\Mapper\PaymentMapper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Helper\ShopNotificationHelper;

class PaymentService {

	/** @var ConfigService $configService */
	private ConfigService $configService;

	/** @var PaymentProvider $paymentProvider */
	private PaymentProvider $paymentProvider;

	/**
	 * PaymentService constructor.
	 *
	 * @param ConfigService   $configService
	 * @param PaymentProvider $paymentProvider
	 */
	public function __construct( ConfigService $configService, PaymentProvider $paymentProvider ) {
		$this->configService   = $configService;
		$this->paymentProvider = $paymentProvider;
	}

	/**
	 * Create a payment.
	 *
	 * @param OrderAdapter   $order Order to create the payment for
	 * @param FeePlanAdapter $feePlanAdapter Fee plan selected by the customer
	 *
	 * @return Payment
	 * @throws PaymentServiceException
	 */
	public function createPayment( OrderAdapter $order, FeePlanAdapter $feePlanAdapter ): Payment {

		try {
			$payment = $this->paymentProvider->createPayment(
				( new PaymentMapper() )->buildPaymentDto(
					$this->configService->getOrigin(),
					$order,
					$feePlanAdapter
				),
				( new OrderMapper() )->buildOrderDto( $order ),
				( new CustomerMapper() )->buildCustomerDto( $order ),
			);
		} catch ( PaymentServiceException $e ) {
			ShopNotificationHelper::notifyError(
				__( 'An error occurred while creating the payment. Please try again.',
					'alma-gateway-for-woocommerce' ),
			);

			throw new PaymentServiceException( 'An error occurred while creating the payment. Please try again.' );
		}

		return $payment;
	}
}
