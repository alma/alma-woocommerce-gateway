<?php
/**
 * Alma_Payment.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\DependenciesError;
use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Models\Alma_Order;
use Alma\Woocommerce\Exceptions\Alma_Amount_Mismatch;
use Alma\Woocommerce\Exceptions\Alma_Api_Fetch_Payments;
use Alma\Woocommerce\Exceptions\Alma_Build_Order;
use Alma\Woocommerce\Exceptions\Alma_Incorrect_Payment;
use Alma\Woocommerce\Exceptions\Alma_Exception;
use Alma\Woocommerce\Helpers\Alma_Constants;
/**
 * Alma_Payment.
 */
class Alma_Payment {


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;


	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;

	/**
	 * Contructor.
	 */
	public function __construct() {
		$this->logger        = new Alma_Logger();
		$this->alma_settings = new Alma_Settings();
	}

	/**
	 * Handle ipn callback.
	 *
	 * @return void
	 */
	public function handle_ipn_callback() {
		$payment_id = $this->get_payment_to_validate();
		$this->validate_payment_from_ipn( $payment_id );
	}

	/**
	 * Webhooks handlers
	 *
	 * PID comes from Alma IPN callback or Alma Checkout page,
	 * it is not a user form submission: Nonce usage is not suitable here.
	 */
	protected function get_payment_to_validate() {
		$id         = sanitize_text_field( $_GET['pid'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$payment_id = isset( $id ) ? $id : null;

		if ( ! $payment_id ) {
			$this->logger->error(
				'Payment validation webhook called without a payment ID.',
				array(
					'Method' => __METHOD__,
					'PID'    => $id,
				)
			);

			wc_add_notice(
				__( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' ),
				Alma_Constants::ERROR
			);

			wp_safe_redirect( wc_get_cart_url() );
			exit();
		}

		return $payment_id;
	}

	/**
	 * Validate payment from ipn.
	 *
	 * @param string $payment_id Payment Id.
	 */
	protected function validate_payment_from_ipn( $payment_id ) {
		try {
			$this->validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			status_header( 500 );
			wp_send_json( array( Alma_Constants::ERROR => $e->getMessage() ) );
		}

		wp_send_json( array( Alma_Constants::SUCCESS => true ) );
	}

	/**
	 * Validate payments.
	 *
	 * @param string $payment_id The payment id.
	 *
	 * @return Alma_Order The order.
	 *
	 * @throws Alma_Amount_Mismatch Amount mismatch.
	 * @throws Alma_Api_Fetch_Payments Can't fetch payments.
	 * @throws Alma_Build_Order    Can't build order.
	 * @throws Alma_Incorrect_Payment Issue with payment.
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws RequestError|Alma_Exception RequestError.
	 */
	public function validate_payment( $payment_id ) {
		$payment = $this->alma_settings->fetch_payment( $payment_id );
		$order   = $this->build_order( $payment->custom_data['order_id'], $payment->custom_data['order_key'], $payment_id );

		if (
			$order->get_order()->has_status(
				apply_filters(
					'alma_valid_order_statuses_for_payment_complete',
					Alma_Constants::$payment_statuses
				)
			)
		) {
			if ( $order->get_total() !== $payment->purchase_amount ) {
				$this->alma_settings->flag_as_fraud( $payment_id, Payment::FRAUD_AMOUNT_MISMATCH );
				throw new Alma_Amount_Mismatch( $payment_id, $order->get_id(), $order->get_total(), $payment->purchase_amount );
			}

			$first_instalment = $payment->payment_plan[0];

			if (
				! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true )
				|| Instalment::STATE_PAID !== $first_instalment->state
			) {
				$this->alma_settings->flag_as_fraud( $payment_id, Payment::FRAUD_STATE_ERROR );

				throw new Alma_Incorrect_Payment( $payment_id, $order->get_id(), $payment->state, $first_instalment->state );
			}

			// If we're down here, everything went OK, and we can validate the order!
			$order->payment_complete( $payment_id );
			$this->update_order_post_meta_if_deferred_trigger( $payment, $order );
		}

		return $order;
	}

	/**
	 * Build the order object.
	 *
	 * @param string $order_id The order id.
	 * @param string $order_key The order key.
	 * @param string $payment_id The payment id.
	 *
	 * @return Alma_Order
	 *
	 * @throws Alma_Build_Order Build order issue.
	 */
	protected function build_order( $order_id, $order_key, $payment_id ) {
		try {
			return new Alma_Order( $order_id, $order_key );
		} catch ( \Exception $e ) {
			throw new Alma_Build_Order( $order_id, $order_key, $payment_id );
		}
	}

	/**
	 * Update the order meta "alma_payment_upon_trigger_enabled" if the payment is upon trigger.
	 *
	 * @param Payment    $payment A payment.
	 * @param Alma_Order $order The order.
	 *
	 * @return void
	 */
	public function update_order_post_meta_if_deferred_trigger( $payment, $order ) {
		if ( $payment->deferred_trigger ) {
			update_post_meta( $order->get_id(), 'alma_payment_upon_trigger_enabled', true );
		}
	}

	/**
	 * Handle customer return.
	 *
	 * @return Alma_Order|null
	 */
	public function handle_customer_return() {
		$payment_id = $this->get_payment_to_validate();

		return $this->validate_payment_on_customer_return( $payment_id );
	}

	/**
	 * Validate payment on customer return.
	 *
	 * @param string $payment_id Payment Id.
	 *
	 * @return Alma_Order|null
	 */
	public function validate_payment_on_customer_return( $payment_id ) {
		$order     = null;
		$error_msg = __( 'There was an error when validating your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );
		try {
			$order = $this->validate_payment( $payment_id );
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );
			$this->redirect_to_cart_with_error( $error_msg );
		}

		if ( ! $order ) {
			$this->redirect_to_cart_with_error( $error_msg );
		}

		return $order;
	}

	/**
	 * Redirect to cart with error.
	 *
	 * @param string $error_msg Error message.
	 */
	protected function redirect_to_cart_with_error( $error_msg ) {
		wc_add_notice( $error_msg, Alma_Constants::ERROR );

		$cart_url = wc_get_cart_url();
		wp_safe_redirect( $cart_url );
		exit();
	}

	/**
	 * Gets description for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 *
	 * @return string
	 */
	public function get_description( $payment_method ) {
		return $this->alma_settings->get_i18n( 'description_' . $payment_method );
	}
}
