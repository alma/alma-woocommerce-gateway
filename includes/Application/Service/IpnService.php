<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\OrderInterface;
use Alma\API\Entity\Payment;
use Alma\Gateway\Application\Exception\PaymentServiceException;
use Alma\Gateway\Application\Exception\SecurityException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\SecurityHelper;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Repository\OrderRepository;
use Alma\Gateway\Plugin;

class IpnService {

	public const IPN_CALLBACK    = 'alma_ipn_callback';
	public const CUSTOMER_RETURN = 'alma_customer_return';

	/** @var OptionsService */
	private OptionsService $options_service;

	/** @var SecurityHelper */
	private SecurityHelper $security_helper;

	/** @var PaymentService */
	private PaymentService $payment_service;

	public function __construct(
		OptionsService $options_service,
		PaymentService $payment_service,
		SecurityHelper $security_helper
	) {
		$this->options_service = $options_service;
		$this->payment_service = $payment_service;
		$this->security_helper = $security_helper;
	}

	/**
	 * Handle the customer return.
	 *
	 * @return void
	 * @todo check nonce
	 */
	public function handle_customer_return(): void {

		// Get the order ID and validate it
		$payment_id = sanitize_text_field( $_GET['pid'] ) ?? null;

		if ( ! $payment_id ) {
			WooCommerceProxy::redirect_to_cart( L10nHelper::__( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.' ) );
			exit();
		}

		// Process the customer return
		try {
			$payment = $this->payment_service->fetch_payment( $payment_id );
			/** @var OrderRepository $order_repository */
			$order_repository = Plugin::get_container()->get( OrderRepository::class );
			$order            = $order_repository->findById(
				$payment->custom_data['order_id'],
				$payment->custom_data['order_key'],
				$payment_id
			);

			if ( $order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manage_mismatch( $order, $payment );
				$this->manage_potential_fraud( $order, $payment );
			}

			$order->payment_complete( $payment_id );
			WooCommerceProxy::empty_cart();
			WooCommerceProxy::notify_success( L10nHelper::__( 'Payment validation done' ) );

		} catch ( \Exception $e ) {
			WooCommerceProxy::notify_error(
				sprintf(
					L10nHelper::__( 'Payment validation error: %s<br>Please try again or contact us if the problem persists.' ),
					$e->getMessage()
				)
			);
		} finally {
			/** @todo The good way is redirect to $gateway->get_return_url() */

			WooCommerceProxy::redirect_after_payment( $order );
		}
	}

	/**
	 * Handle IPN callback.
	 *
	 * @return void
	 * @todo check nonce
	 */
	public function handle_ipn_callback(): void {

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
			$this->security_helper->validate_ipn_signature(
				$payment_id,
				$this->options_service->get_active_api_key(),
				$_SERVER['HTTP_X_ALMA_SIGNATURE']
			);
		} catch ( SecurityException $e ) {
			wp_send_json( array( 'error' => $e->getMessage() ), 403 );
		}

		// Process the IPN callback
		$code   = 200;
		$result = array( 'success' => true );

		try {
			$payment = $this->payment_service->fetch_payment( $payment_id );
			/** @var OrderRepository $order_repository */
			$order_repository = Plugin::get_container()->get( OrderRepository::class );
			$order            = $order_repository->findById(
				$payment->custom_data['order_id'],
				$payment->custom_data['order_key'],
				$payment_id
			);
			if ( $order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manage_mismatch( $order, $payment );
				$this->manage_potential_fraud( $order, $payment );
			}
		} catch ( \Exception $e ) {
			$code    = 500;
			$message = sprintf( ' %s - Payment id : "%s"', $e->getMessage(), $payment_id );
			$result  = array( 'error' => $message );
		} finally {
			wp_send_json( $result, $code );
		}
	}

	/**
	 * @throws PaymentServiceException
	 * @throws PaymentServiceException
	 */
	private function manage_mismatch( OrderInterface $order, Payment $payment ): void {

		$order_total = WooCommerceProxy::get_order_total( $order->get_id() );
		if ( $order_total !== $payment->purchase_amount ) {
			$this->payment_service->flag_as_fraud( $payment->id, Payment::FRAUD_AMOUNT_MISMATCH );
			$order->update_status( 'failed', Payment::FRAUD_AMOUNT_MISMATCH );

			throw new PaymentServiceException( 'Potential fraud detected: order total does not match payment amount.' );
		}
	}

	/**
	 * @throws PaymentServiceException
	 * @throws PaymentServiceException
	 */
	private function manage_potential_fraud( OrderInterface $order, $payment ): void {

		if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
			$this->payment_service->flag_as_fraud( $payment->id, Payment::FRAUD_STATE_ERROR );
			$order->update_status( 'failed', Payment::FRAUD_STATE_ERROR );

			throw new PaymentServiceException( 'Potential fraud detected: payment state is not in progress or paid.' );
		}
	}
}
