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
	 * Create Payment data for Alma API request from Woocommerce Cart.
	 *
	 * @return array
	 */
	public static function get_payment_payload_from_cart() {
		$customer = new Alma_WC_Model_Customer();
		$cart     = new Alma_WC_Model_Cart();
		$data     = array(
			'payment' =>
				array(
					'return_url'       => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::CUSTOMER_RETURN ),
					'ipn_callback_url' => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::IPN_CALLBACK ),
					'purchase_amount'  => $cart->get_total(),
					'locale'           => apply_filters( 'alma_wc_checkout_payment_locale', get_locale() ),
				),
		);

		$data['payment']['billing_address']  = $customer->get_billing_address();
		$data['payment']['shipping_address'] = $customer->get_shipping_address();
		$data['customer']                    = $customer->get_data();

		return $data;
	}

	/**
	 * Create Payment data for Alma API request from Woocommerce Order.
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
			$logger->error( 'Error getting payment info from order: ' . $e->getMessage() );

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
			),
			'order'   => array(
				'merchant_reference' => $order->get_order_reference(),
				'merchant_url'       => $order->get_merchant_url(),
				'customer_url'       => $order->get_customer_url(),
			),
		);

		if ( $order->has_billing_address() ) {
			$billing_address                    = $order->get_billing_address();
			$data['payment']['billing_address'] = $billing_address;

			$data['customer'] = array(
				'first_name' => $billing_address['first_name'],
				'last_name'  => $billing_address['last_name'],
				'email'      => $billing_address['email'],
				'phone'      => $billing_address['phone'],
				'addresses'  => array( $billing_address ),
			);
		}

		if ( $order->has_shipping_address() ) {
			$shipping_address                    = $order->get_shipping_address();
			$data['payment']['shipping_address'] = $shipping_address;

			$customer_data = array(
				'first_name' => $shipping_address['first_name'],
				'last_name'  => $shipping_address['last_name'],
				'addresses'  => array( $shipping_address ),
			);

			$data['customer'] = alma_wc_array_merge_recursive( $data['customer'], $customer_data );
		}

		// Merge built data on data extracted from Cart to have as much data as possible.
		return alma_wc_array_merge_recursive( self::get_payment_payload_from_cart(), $data );
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
	 * Create Eligibility data for Alma API request from Woocommerce Cart.
	 *
	 * @return array Payload to request eligibility v2 endpoint.
	 */
	public static function get_eligibility_payload_from_cart() {
		$cart     = new Alma_WC_Model_Cart();
		$customer = new Alma_WC_Model_Customer();

		$data = array(
			'purchase_amount' => $cart->get_total(),
			'queries'         => alma_wc_plugin()->get_eligible_plans_for_cart(),
			'locale'          => apply_filters( 'alma_wc_eligibility_locale', get_locale() ),
		);

		$billing_country  = $customer->get_billing_address()['country'];
		$shipping_country = $customer->get_shipping_address()['country'];

		if ( $billing_country ) {
			$data['billing_address'] = array( 'country' => $billing_country );
		}
		if ( $shipping_country ) {
			$data['shipping_address'] = array( 'country' => $shipping_country );
		}

		return $data;
	}
}
