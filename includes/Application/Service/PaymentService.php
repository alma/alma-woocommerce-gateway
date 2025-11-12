<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Exception\Service\API\PaymentServiceException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Mapper\CustomerMapper;
use Alma\Gateway\Application\Mapper\OrderMapper;
use Alma\Gateway\Application\Mapper\PaymentMapper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Helper\InPageHelper;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Plugin;

class PaymentService {

	private ConfigService $configService;

	private PaymentProvider $paymentProvider;

	private InPageHelper $inPageHelper;

	public function __construct( ConfigService $configService, PaymentProvider $paymentProvider, InPageHelper $inPageHelper ) {
		$this->configService   = $configService;
		$this->paymentProvider = $paymentProvider;
		$this->inPageHelper    = $inPageHelper;
	}

	public function createPayment( bool $isInPage, OrderAdapter $order, FeePlanAdapter $feePlanAdapter ): array {

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
			/** @var NotificationHelper $notificationHelper */
			$notificationHelper = Plugin::get_container()->get( NotificationHelper::class );
			$notificationHelper->notifyError(
				L10nHelper::__( 'An error occurred while creating the payment. Please try again.' . $e->getMessage() ),
			);

			return array(
				'result'   => 'failure',
				'error'    => L10nHelper::__( 'An error occurred while creating the payment. Please try again.' ),
				'redirect' => '',
			);
		}

		// Determine redirection URL based on in-page or standard flow
		if ( $isInPage ) {
			$redirectionUrl = $this->inPageHelper->getInPageRedirectionUrl( $payment->getId() );
		} else {
			$redirectionUrl = $payment->geturl();
		}

		return array(
			'result'     => 'success',
			'redirect'   => $redirectionUrl,
			'payment_id' => $payment->getId(),
		);
	}
}
