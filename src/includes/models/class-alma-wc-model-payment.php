<?php
/**
 * Alma payment
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Model_Payment
 */
class Alma_WC_Model_Payment {

    /**
     * Create Payment data for Alma API request from WooCommerce Order.
     *
     * @param int   $order_id Order ID.
     * @param array $fee_plan_definition Fee plan definition.
     *
     * @return array
     */
    public static function get_payment_payload_from_order( $order_id, $fee_plan_definition ) {
        try {
            $order = new Alma_WC_Model_Order( $order_id );
        } catch ( Exception $e ) {
            $logger = new Alma_WC_Logger();

            $logger->error(
                'Error getting payment info from order.',
                array(
                    'Method'           => __METHOD__,
                    'OrderId'          => $order_id,
                    'ExceptionMessage' => $e->getMessage(),
                )
            );

            return array();
        }

        $data = array(
            'payment' => array(
                'purchase_amount'     => $order->get_total(),
                'return_url'          => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::CUSTOMER_RETURN ),
                'ipn_callback_url'    => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::IPN_CALLBACK ),
                'customer_cancel_url' => self::get_customer_cancel_url(),
                'installments_count'  => $fee_plan_definition['installments_count'],
                'deferred_days'       => $fee_plan_definition['deferred_days'],
                'deferred_months'     => $fee_plan_definition['deferred_months'],
                'custom_data'         => array(
                    'order_id'  => $order_id,
                    'order_key' => $order->get_order_key(),
                ),
                'locale'              => apply_filters( 'alma_wc_checkout_payment_user_locale', get_locale() ),
            ),
            'order'   => array(
                'merchant_reference' => $order->get_order_reference(),
                'merchant_url'       => $order->get_merchant_url(),
                'customer_url'       => $order->get_customer_url(),
            ),
        );

        if ( Alma_WC_Payment_Upon_Trigger::does_payment_upon_trigger_apply_for_this_fee_plan( $fee_plan_definition ) ) {
            $data['payment']['deferred']             = 'trigger';
            $data['payment']['deferred_description'] = Alma_WC_Payment_Upon_Trigger::get_display_text();
        }

        $data['customer']                = array();
        $data['customer']['addresses']   = array();
        $data['customer']['is_business'] = false;
        if ( $order->has_billing_address() ) {
            $billing_address                    = $order->get_billing_address();
            $data['payment']['billing_address'] = $billing_address;

            $data['customer']['first_name']  = $billing_address['first_name'];
            $data['customer']['last_name']   = $billing_address['last_name'];
            $data['customer']['email']       = $billing_address['email'];
            $data['customer']['phone']       = $billing_address['phone'];
            $data['customer']['addresses'][] = $billing_address;

            if ( $order->is_business() ) {
                $data['customer']['is_business']   = true;
                $data['customer']['business_name'] = $order->get_business_name();
            }
        }

        if ( $order->has_shipping_address() ) {
            $shipping_address                    = $order->get_shipping_address();
            $data['payment']['shipping_address'] = $shipping_address;
            $data['customer']['addresses'][]     = $shipping_address;
        }

        return apply_filters( 'alma_wc_get_payment_payload_from_order', $data );
    }

    /**
     * Get customer cancel url.
     *
     * @return string
     * @noinspection PhpDeprecationInspection
     */
    public static function get_customer_cancel_url() {
        if ( version_compare( wc()->version, '2.5.0', '<' ) ) {
            return wc()->cart->get_checkout_url();
        } else {
            return wc_get_checkout_url();
        }
    }

    /**
     * Create Eligibility data for Alma API request from WooCommerce Cart.
     *
     * @return array Payload to request eligibility v2 endpoint.
     */
    public static function get_eligibility_payload_from_cart() {
        $cart     = new Alma_WC_Model_Cart();
        $customer = new Alma_WC_Model_Customer();

        $data = array(
            'purchase_amount' => $cart->get_total_in_cents(),
            'queries'         => alma_wc_plugin()->get_eligible_plans_for_cart(),
            'locale'          => apply_filters( 'alma_wc_eligibility_user_locale', get_locale() ),
        );

        $billing_country  = $customer->get_billing_country();
        $shipping_country = $customer->get_shipping_country();

        if ( $billing_country ) {
            $data['billing_address'] = array( 'country' => $billing_country );
        }
        if ( $shipping_country ) {
            $data['shipping_address'] = array( 'country' => $shipping_country );
        }

        return $data;
    }
}
