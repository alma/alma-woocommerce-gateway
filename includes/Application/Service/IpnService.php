<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\Entity\Payment;
use Alma\API\Domain\Helper\NavigationHelperInterface;
use Alma\API\Domain\Helper\NotificationHelperInterface;
use Alma\Gateway\Application\Exception\Service\API\PaymentServiceException;
use Alma\Gateway\Application\Exception\Service\IpnServiceException;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Infrastructure\Helper\ParameterHelper;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Plugin;

class IpnService {

	public const IPN_CALLBACK = 'alma_ipn_callback';
	public const CUSTOMER_RETURN = 'alma_customer_return';

	/** @var ConfigService */
	private ConfigService $configService;

	/** @var IpnHelper */
	private IpnHelper $ipnHelper;

	/** @var PaymentService */
	private PaymentService $paymentService;

	/** @var NotificationHelper */
	private NotificationHelper $notificationHelper;

	/** @var CartAdapterInterface */
	private CartAdapterInterface $cartAdapter;

	/** @var NavigationHelperInterface */
	private NavigationHelperInterface $navigationHelper;

	public function __construct(
		ConfigService $configService,
		PaymentService $paymentService,
		NotificationHelperInterface $notificationHelper,
		CartAdapterInterface $cartAdapter,
		NavigationHelperInterface $navigationHelper,
		IpnHelper $ipnHelper
	) {
		$this->configService      = $configService;
		$this->paymentService     = $paymentService;
		$this->notificationHelper = $notificationHelper;
		$this->cartAdapter        = $cartAdapter;
		$this->navigationHelper   = $navigationHelper;
		$this->ipnHelper          = $ipnHelper;
	}

	/**
	 * Handle the customer return.
	 *
	 * @return void
	 *
	 * @throws ContainerServiceException
	 * @todo check nonce
	 */
	public function handleCustomerReturn(): void {

		$paymentId = ParameterHelper::checkAndCleanParam( $_GET['pid'] );

		if ( ! $paymentId ) {
			$this->navigationHelper->redirectToCart( L10nHelper::__( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.' ) );
			exit();
		}

		// Process the customer return
		try {
			$payment = $this->paymentService->fetchPayment( $paymentId );
			/** @var OrderRepository $order_repository */
			$order_repository = Plugin::get_container()->get( OrderRepository::class );
			$order            = $order_repository->getById(
				$payment->getCustomData()['order_id'],
				$payment->getCustomData()['order_key'],
				$paymentId
			);

			if ( $order->hasStatus( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manageMismatch( $order, $payment );
				$this->managePotentialFraud( $order, $payment );
			}

			if ( ! $order->paymentComplete( $paymentId ) ) {
				$this->navigationHelper->redirectToCart( L10nHelper::__( 'Payment validation error: order not found.<br>Please try again or contact us if the problem persists.' ) );
				exit();
			}

			$this->cartAdapter->emptyCart();
			$this->notificationHelper->notifySuccess( L10nHelper::__( 'Payment validation done' ) );

		} catch ( IpnServiceException|PaymentServiceException|ProductRepositoryException $e ) {
			$this->notificationHelper->notifyError(
				sprintf(
					L10nHelper::__( 'Payment validation error: %s<br>Please try again or contact us if the problem persists.' ),
					$e->getMessage()
				)
			);
		} finally {
			/** @todo The good way is redirect to $gateway->get_return_url() */
			$this->navigationHelper->redirectAfterPayment( $order );
		}
	}

	/**
	 * Handle IPN callback.
	 *
	 * @return void
	 * @throws ContainerServiceException
	 * @todo check nonce
	 */
	public function handleIpnCallback(): void {

		$paymentId = ParameterHelper::checkAndCleanParam( $_GET['pid'] );

		if ( ! $paymentId ) {
			$this->ipnHelper->parameterError();
		}

		// Get the signature and validate it
		if ( ! array_key_exists( 'HTTP_X_ALMA_SIGNATURE', $_SERVER ) ) {
			$this->ipnHelper->signatureNotExistError();
		}
		try {
			$this->ipnHelper->validateIpnSignature(
				$paymentId,
				$this->configService->getActiveApiKey(),
				$_SERVER['HTTP_X_ALMA_SIGNATURE']
			);
		} catch ( IpnServiceException $e ) {
			$this->ipnHelper->unauthorizedError( $e->getMessage() );
		}

		// Process the IPN callback
		$order = null;

		try {
			$payment = $this->paymentService->fetchPayment( $paymentId );
			/** @var OrderRepository $orderRepository */
			$orderRepository = Plugin::get_container()->get( OrderRepository::class );
			$order           = $orderRepository->getById(
				$payment->getCustomData()['order_id'],
				$payment->getCustomData()['order_key'],
				$paymentId
			);
		} catch ( ProductRepositoryException|PaymentServiceException $e ) {
			$this->ipnHelper->parameterError( 'Payment validation error: ' . $e->getMessage() );
		}

		try {
			if ( $order->hasStatus( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manageMismatch( $order, $payment );
				$this->managePotentialFraud( $order, $payment );
			}
		} catch ( IpnServiceException $e ) {
			$this->ipnHelper->potentialFraudError( $e->getMessage() );
		}

		$this->ipnHelper->success();
	}

	/**
	 * @throws IpnServiceException
	 */
	private function manageMismatch( OrderAdapterInterface $order, Payment $payment ): void {
		$order_total = $order->getTotal();
		if ( $order_total !== $payment->getPurchaseAmount() ) {
			try {
				$this->paymentService->flagAsFraud( $payment->getId(), Payment::FRAUD_AMOUNT_MISMATCH );
			} catch ( PaymentServiceException $e ) {
				throw new IpnServiceException( $e->getMessage() );
			}
			$order->updateStatus( 'failed', Payment::FRAUD_AMOUNT_MISMATCH );
			throw new IpnServiceException( 'Potential fraud detected: order total does not match payment amount.' );
		}
	}

	/**
	 * @throws IpnServiceException
	 */
	private function managePotentialFraud( OrderAdapterInterface $order, Payment $payment ): void {
		if ( ! in_array( $payment->getState(), array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
			try {
				$this->paymentService->flagAsFraud( $payment->getId(), Payment::FRAUD_STATE_ERROR );
			} catch ( PaymentServiceException $e ) {
				throw new IpnServiceException( $e->getMessage() );
			}
			$order->updateStatus( 'failed', Payment::FRAUD_STATE_ERROR );

			throw new IpnServiceException( 'Potential fraud detected: payment state is not in progress or paid.' );
		}
	}
}
