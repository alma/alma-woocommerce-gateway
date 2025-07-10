<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\Entities\Payment;
use Alma\Gateway\Business\Exception\PaymentServiceException;
use Alma\Gateway\Business\Exception\SecurityException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\SecurityHelper;
use Alma\Gateway\Business\Service\API\PaymentService;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use WC_Order;

class IpnService {

	public const IPN_CALLBACK    = 'alma_ipn_callback';
	public const CUSTOMER_RETURN = 'alma_customer°return';

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
			$payment  = $this->payment_service->fetch_payment( $payment_id );
			$wc_order = WooCommerceProxy::get_order(
				$payment->custom_data['order_id'],
				$payment->custom_data['order_key'],
				$payment_id
			);

			if ( $wc_order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manage_mismatch( $wc_order, $payment );
				$this->manage_potential_fraud( $wc_order, $payment );
			}

			$wc_order->payment_complete( $payment_id );
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

			WooCommerceProxy::redirect_after_payment( $wc_order );
		}
	}

	/**
	 * Handle IPN callback.
	 *
	 * @return void
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
			// var_dump( hash_hmac( 'sha256', $payment_id, $this->options_service->get_active_api_key() ) );
			// die;
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
			$payment  = $this->payment_service->fetch_payment( $payment_id );
			$wc_order = WooCommerceProxy::get_order(
				$payment->custom_data['order_id'],
				$payment->custom_data['order_key'],
				$payment_id
			);
			if ( $wc_order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
				$this->manage_mismatch( $wc_order, $payment );
				$this->manage_potential_fraud( $wc_order, $payment );
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
	private function manage_mismatch( WC_Order $wc_order, Payment $payment ): void {

		$order_total = WooCommerceProxy::get_order_total( $wc_order->get_id() );
		if ( $order_total !== $payment->purchase_amount ) {
			$this->payment_service->flag_as_fraud( $payment->id, Payment::FRAUD_AMOUNT_MISMATCH );
			$wc_order->update_status( 'failed', Payment::FRAUD_AMOUNT_MISMATCH );

			throw new PaymentServiceException(
				$payment->id,
				$wc_order->get_id(),
				$order_total,
				$payment->purchase_amount
			);
		}
	}

	/**
	 * @throws PaymentServiceException
	 * @throws PaymentServiceException
	 */
	private function manage_potential_fraud( WC_Order $wc_order, $payment ): void {

		if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ) {
			$this->payment_service->flag_as_fraud( $payment->id, Payment::FRAUD_STATE_ERROR );
			$wc_order->update_status( 'failed', Payment::FRAUD_STATE_ERROR );

			throw new PaymentServiceException( $payment->id, $wc_order->get_id(), $payment->state );
		}
	}
}
