<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Exception\Helper\IpnHelperException;
use Alma\Gateway\Application\Exception\Provider\PaymentProviderException;
use Alma\Gateway\Application\Exception\Service\FraudServiceException;
use Alma\Gateway\Application\Exception\Service\IpnServiceException;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Infrastructure\Exception\Repository\OrderRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ParameterHelper;
use Alma\Gateway\Infrastructure\Helper\ShopNotificationHelper;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Plugin;
use Alma\Plugin\Infrastructure\Adapter\CartAdapterInterface;
use Alma\Plugin\Infrastructure\Helper\NavigationHelperInterface;

class IpnService {
	public const IPN_CALLBACK = 'alma_ipn_callback';
	public const CUSTOMER_RETURN = 'alma_customer_return';

	/** @var ConfigService */
	private ConfigService $configService;

	/** @var IpnHelper */
	private IpnHelper $ipnHelper;

	/** @var PaymentProvider */
	private PaymentProvider $paymentService;

	/** @var CartAdapterInterface */
	private CartAdapterInterface $cartAdapter;

	/** @var NavigationHelperInterface */
	private NavigationHelperInterface $navigationHelper;

	/** @var FraudService */
	private FraudService $fraudService;

	public function __construct(
		ConfigService $configService,
		FraudService $fraudService,
		PaymentProvider $paymentService,
		CartAdapterInterface $cartAdapter,
		NavigationHelperInterface $navigationHelper,
		IpnHelper $ipnHelper
	) {
		$this->configService    = $configService;
		$this->fraudService     = $fraudService;
		$this->paymentService   = $paymentService;
		$this->cartAdapter      = $cartAdapter;
		$this->navigationHelper = $navigationHelper;
		$this->ipnHelper        = $ipnHelper;
	}

	/**
	 * Handle the customer return.
	 *
	 * @return void
	 * @throws IpnServiceException
	 *
	 * @todo check nonce
	 */
	public function handleCustomerReturn(): void {

		$paymentId = ParameterHelper::checkAndCleanParam( $_GET['pid'] );

		if ( ! $paymentId ) {
			$this->navigationHelper->redirectToCart( __( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.',
				'alma-gateway-for-woocommerce' ) );
			exit();
		}

		// Process the customer return
		try {
			$payment = $this->paymentService->fetchPayment( $paymentId );
			/** @var OrderRepository $order_repository */
			$order_repository = Plugin::get_container()->get( OrderRepository::class );
			try {
				$order = $order_repository->getById(
					$payment->getCustomData()['order_id'],
					$payment->getCustomData()['order_key'],
					$paymentId
				);
			} catch ( OrderRepositoryException $e ) {
				ShopNotificationHelper::notifyError(
					__( 'Payment validation error<br>Please try again or contact us if the problem persists.',
						'alma-gateway-for-woocommerce' ),
				);
			}

			if ( $order->hasStatus( array( 'on-hold', 'pending', 'failed' ) ) ) {
				try {
					$this->fraudService->manageMismatch( $order, $payment );
					$this->fraudService->managePotentialFraud( $order, $payment );
				} catch ( FraudServiceException $e ) {
					throw new IpnServiceException( $e );
				}
			}

			if ( ! $order->paymentComplete( $paymentId ) ) {
				$this->navigationHelper->redirectToCart( __( 'Payment validation error: order not found.<br>Please try again or contact us if the problem persists.',
					'alma-gateway-for-woocommerce' ) );
				exit();
			}

			$this->cartAdapter->emptyCart();
			ShopNotificationHelper::notifySuccess( __( 'Payment validation done',
				'alma-gateway-for-woocommerce' ) );

		} catch ( PaymentProviderException $e ) {
			ShopNotificationHelper::notifyError(
				__( 'Payment validation error<br>Please try again or contact us if the problem persists.',
					'alma-gateway-for-woocommerce' ),
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
		} catch ( IpnHelperException $e ) {
			$this->ipnHelper->unauthorizedError( $e->getMessage() );
		}

		// Process the IPN callback
		$order = null;

		try {
			$payment = $this->paymentService->fetchPayment( $paymentId );
			/** @var OrderRepository $orderRepository */
			$orderRepository = Plugin::get_container()->get( OrderRepository::class );

			$order = $orderRepository->getById(
				$payment->getCustomData()['order_id'],
				$payment->getCustomData()['order_key'],
				$paymentId
			);

		} catch ( PaymentProviderException|OrderRepositoryException $e ) {
			$this->ipnHelper->parameterError( 'Payment validation error: ' . $e->getMessage() );
		}

		try {
			if ( $order->hasStatus( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->fraudService->manageMismatch( $order, $payment );
				$this->fraudService->managePotentialFraud( $order, $payment );
			}
		} catch ( FraudServiceException $e ) {
			$this->ipnHelper->potentialFraudError( $e->getMessage() );
		}

		$this->ipnHelper->success();
	}
}
