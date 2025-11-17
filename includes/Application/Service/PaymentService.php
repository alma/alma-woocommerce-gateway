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

	/** @var ConfigService $configService */
	private ConfigService $configService;

	/** @var PaymentProvider $paymentProvider */
	private PaymentProvider $paymentProvider;

	/** @var InPageHelper $inPageHelper */
	private InPageHelper $inPageHelper;

	/**
	 * PaymentService constructor.
	 *
	 * @param ConfigService   $configService
	 * @param PaymentProvider $paymentProvider
	 * @param InPageHelper    $inPageHelper
	 */
	public function __construct( ConfigService $configService, PaymentProvider $paymentProvider, InPageHelper $inPageHelper ) {
		$this->configService   = $configService;
		$this->paymentProvider = $paymentProvider;
		$this->inPageHelper    = $inPageHelper;
	}

	/**
	 * Create a payment.
	 *
	 * @param bool           $isInPage Indicates if the payment is created for an in-page flow
	 * @param OrderAdapter   $order Order to create the payment for
	 * @param FeePlanAdapter $feePlanAdapter Fee plan selected by the customer
	 * @param bool           $inPageRedirectFallback return a fallback URL for in-page redirection (old behavior)
	 *
	 * @return array
	 */
	public function createPayment( bool $isInPage, OrderAdapter $order, FeePlanAdapter $feePlanAdapter, bool $inPageRedirectFallback = false ): array {

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

		// Determine redirection URL based id needed
		$redirectionUrl = '';
		if ( $isInPage ) {
			if ( $inPageRedirectFallback ) {
				// In-page checkout with fallback redirection
				$redirectionUrl = $this->inPageHelper->getInPageRedirectionFallbackUrl( $payment->getId() );
			}
		} else {
			// Classic checkout redirection
			$redirectionUrl = $payment->geturl();
		}

		return array(
			'result'          => 'success',
			'redirect'        => $redirectionUrl,
			'alma_payment_id' => $payment->getId(),
		);
	}
}
