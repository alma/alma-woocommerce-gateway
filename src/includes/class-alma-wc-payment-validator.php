<?php
/**
 * Alma payment validator
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;
use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Alma_WC_Payment_Validator
 */
class Alma_WC_Payment_Validator {

    /**
     * Validate payment
     *
     * @param string $payment_id Payment Id.
     *
     * @return Alma_WC_Model_Order
     *
     * @throws Alma_WC_Payment_Validation_Error Alma WC payment validation error.
     */
    public static function validate_payment( $payment_id ) {
        $logger = new Alma_WC_Logger();

        $alma = alma_wc_plugin()->get_alma_client();
        if ( ! $alma ) {
            throw new Alma_WC_Payment_Validation_Error( 'api_client_init' );
        }

        try {
            $payment = $alma->payments->fetch( $payment_id );
        } catch ( RequestError $e ) {
            $logger->error(
                'Error while fetching payment with id.',
                array(
                    'Method'           => __METHOD__,
                    'PaymentId'        => $payment_id,
                    'ExceptionMessage' => $e->getMessage(),
                )
            );

            throw new Alma_WC_Payment_Validation_Error( 'payment_fetch_error' );
        }

        try {
            $order = new Alma_WC_Model_Order( $payment->custom_data['order_id'], $payment->custom_data['order_key'] );
        } catch ( \Exception $e ) {
            $logger->error(
                'Error getting order associated to payment.',
                array(
                    'Method'           => __METHOD__,
                    'PaymentId'        => $payment_id,
                    'ExceptionMessage' => $e->getMessage(),
                )
            );

            throw new Alma_WC_Payment_Validation_Error( 'order_fetch_error' );
        }

        if ( $order->get_wc_order()->has_status( apply_filters( 'alma_wc_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ) ) ) ) {
            if ( $order->get_total() !== $payment->purchase_amount ) {
                $logger->error(
                    'The total order amount does not match the purchase amount of the payment.',
                    array(
                        'Method'                => __METHOD__,
                        'OrderId'               => $order->get_id(),
                        'OrderAmount'           => $order->get_total(),
                        'PaymentId'             => $payment_id,
                        'PaymentPurchaseAmount' => $payment->purchase_amount,
                    )
                );

                try {
                    $alma->payments->flagAsPotentialFraud( $payment_id, Payment::FRAUD_AMOUNT_MISMATCH );
                } catch ( RequestError $e ) {
                    $logger->error(
                        'FlagAsPotentialFraud request error (total amount issue).',
                        array(
                            'Method'           => __METHOD__,
                            'ExceptionMessage' => $e->getMessage(),
                        )
                    );
                }

                throw new Alma_WC_Payment_Validation_Error( sprintf( "Order %s total (%s) does not match purchase amount of '%s' (%s)", $order->get_id(), $order->get_total(), $payment_id, $payment->purchase_amount ) );
            }

            $first_instalment = $payment->payment_plan[0];
            if ( ! in_array( $payment->state, array( Payment::STATE_IN_PROGRESS, Payment::STATE_PAID ), true ) ||
                Instalment::STATE_PAID !== $first_instalment->state ) {

                $logger->error(
                    'State payment error/incorrect.',
                    array(
                        'Method'               => __METHOD__,
                        'OrderId'              => $order->get_id(),
                        'PaymentId'            => $payment_id,
                        'PaymentState'         => $payment->state,
                        'FirstInstalmentState' => $first_instalment->state,
                    )
                );

                try {
                    $alma->payments->flagAsPotentialFraud( $payment_id, Payment::FRAUD_STATE_ERROR );
                } catch ( RequestError $e ) {
                    $logger->error(
                        'FlagAsPotentialFraud request error (state payment incorrect).',
                        array(
                            'Method'           => __METHOD__,
                            'ExceptionMessage' => $e->getMessage(),
                        )
                    );
                }

                throw new Alma_WC_Payment_Validation_Error( sprintf( "Payment '%s': state incorrect %s & %s", $payment_id, $payment->state, $first_instalment->state ) );
            }

            // If we're down here, everything went OK, and we can validate the order!
            $order->payment_complete( $payment_id );

            self::update_order_post_meta_if_deferred_trigger( $payment, $order );
        }

        return $order;
    }

    /**
     * Update the order meta "alma_payment_upon_trigger_enabled" if the payment is upon trigger.
     *
     * @param Payment             $payment A payment.
     * @param Alma_WC_Model_Order $order The order.
     * @return void
     */
    public static function update_order_post_meta_if_deferred_trigger( $payment, $order ) {
        if ( $payment->deferred_trigger ) {
            update_post_meta( $order->get_id(), 'alma_payment_upon_trigger_enabled', true );
        }
    }

}
