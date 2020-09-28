<?php

use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;
use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AlmaPaymentValidationError extends \Exception {}


class Alma_WC_Payment_Validator {

	/**
	 * @param $payment_id
	 * @param $success_url
	 * @return mixed
	 * @throws AlmaPaymentValidationError
	 */
	public static function validate_payment( $payment_id ) {
		$logger = new Alma_WC_Logger();

		$alma = alma_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			throw new AlmaPaymentValidationError( 'api_client_init' );
		}

		try {
			$payment = $alma->payments->fetch( $payment_id );
		} catch ( RequestError $e ) {
			$logger->error( 'Error while fetching payment with id ' . $payment_id . ': ' . $e->getMessage() );
			throw new AlmaPaymentValidationError( 'payment_fetch_error' );
		}

		try {
			$order = new Alma_WC_Order( $payment->custom_data['order_id'], $payment->custom_data['order_key'] );
		} catch ( \Exception $e ) {
			$logger->error( "Error getting order associated to payment '$payment_id': " . $e->getMessage() );
			throw new AlmaPaymentValidationError( 'order_fetch_error' );
		}

		if ( $order->get_wc_order()->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ) ) ) ) {
			if ( $order->get_total() !== $payment->purchase_amount ) {
				$error = "Order {$order->get_id()} total ({$order->get_total()}) does not match purchase amount of '$payment_id' ({$payment->purchase_amount})";
				$logger->error( $error );

				try {
					$alma->payments->flagAsPotentialFraud( $payment_id, Payment::FRAUD_AMOUNT_MISMATCH );
				} catch ( RequestError $e ) {
				}

				throw new AlmaPaymentValidationError( $error );
			}

			$first_instalment = $payment->payment_plan[0];
			if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ) ) ||
				$first_instalment->state !== Instalment::STATE_PAID ) {

				$error = "Payment '$payment_id': state incorrect {$payment->state} & {$first_instalment->state}";
				$logger->error( $error );

				try {
					$alma->payments->flagAsPotentialFraud( $payment_id, Payment::FRAUD_STATE_ERROR );
				} catch ( RequestError $e ) {
				}

				throw new AlmaPaymentValidationError( $error );
			}

			// If we're down here, everything went OK and we  can validate the order!
			$order->payment_complete( $payment_id );
		}

		return $order;
	}

}
