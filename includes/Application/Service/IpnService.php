<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\Exception\PaymentServiceException;
use Alma\API\Domain\Exception\SecurityException;
use Alma\API\Domain\Exception\Service\ContainerServiceException;
use Alma\API\Domain\Helper\NavigationHelperInterface;
use Alma\API\Domain\Helper\NotificationHelperInterface;
use Alma\API\Entity\Payment;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Plugin;
use Exception;

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
	 * @throws ContainerServiceException
	 * @todo check nonce
	 */
	public function handleCustomerReturn(): void {

		// Get the order ID and validate it
		$payment_id = sanitize_text_field( $_GET['pid'] ) ?? null;

		if ( ! $payment_id ) {
			$this->navigationHelper->redirectToCart( L10nHelper::__( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.' ) );
			exit();
		}

		// Process the customer return
		try {
			$payment = $this->paymentService->fetchPayment( $payment_id );
			/** @var OrderRepository $order_repository */
			$order_repository = Plugin::get_container()->get( OrderRepository::class );
			$order            = $order_repository->findById(
				$payment->custom_data['order_id'],
				$payment->custom_data['order_key'],
				$payment_id
			);

			if ( $order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manageMismatch( $order, $payment );
				$this->managePotentialFraud( $order, $payment );
			}

			$order->payment_complete( $payment_id );
			$this->cartAdapter->emptyCart();
			$this->notificationHelper->notifySuccess( L10nHelper::__( 'Payment validation done' ) );

		} catch ( Exception $e ) {
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
	 * @todo check nonce
	 */
	public function handleIpnCallback(): void {

		// Get the order ID and validate it
		$payment_id = sanitize_text_field( $_GET['pid'] ) ?? null;

		if ( ! $payment_id ) {
			wp_send_json( array( 'error' => 'Payment validation error: no ID provided.' ), 403 );
		}

		// Get the signature and validate it
		if ( ! array_key_exists( 'HTTP_X_ALMA_SIGNATURE', $_SERVER ) ) {
			wp_send_json( array( 'error' => 'Header key X-Alma-Signature does not exist.' ), 403 );
		}
		try {
			$this->ipnHelper->validateIpnSignature(
				$payment_id,
				$this->configService->getActiveApiKey(),
				$_SERVER['HTTP_X_ALMA_SIGNATURE']
			);
		} catch ( SecurityException $e ) {
			wp_send_json( array( 'error' => $e->getMessage() ), 403 );
		}

		// Process the IPN callback
		$code   = 200;
		$result = array( 'success' => true );

		try {
			$payment = $this->paymentService->fetchPayment( $payment_id );
			/** @var OrderRepository $order_repository */
			$order_repository = Plugin::get_container()->get( OrderRepository::class );
			$order            = $order_repository->findById(
				$payment->custom_data['order_id'],
				$payment->custom_data['order_key'],
				$payment_id
			);
			if ( $order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manageMismatch( $order, $payment );
				$this->managePotentialFraud( $order, $payment );
			}
		} catch ( Exception $e ) {
			$code    = 500;
			$message = sprintf( ' %s - Payment id : "%s"', $e->getMessage(), $payment_id );
			$result  = array( 'error' => $message );
		} finally {
			wp_send_json( $result, $code );
		}
	}

	/**
	 * @throws PaymentServiceException
	 */
	private function manageMismatch( OrderAdapterInterface $order, Payment $payment ): void {

		$order_total = $order->getOrderTotal( $order->get_id() );
		if ( $order_total !== $payment->purchase_amount ) {
			$this->paymentService->flagAsFraud( $payment->id, Payment::FRAUD_AMOUNT_MISMATCH );
			$order->update_status( 'failed', Payment::FRAUD_AMOUNT_MISMATCH );

			throw new PaymentServiceException( 'Potential fraud detected: order total does not match payment amount.' );
		}
	}

	/**
	 * @throws PaymentServiceException
	 */
	private function managePotentialFraud( OrderAdapterInterface $order, $payment ): void {

		if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
			$this->paymentService->flagAsFraud( $payment->id, Payment::FRAUD_STATE_ERROR );
			$order->update_status( 'failed', Payment::FRAUD_STATE_ERROR );

			throw new PaymentServiceException( 'Potential fraud detected: payment state is not in progress or paid.' );
		}
	}
}
