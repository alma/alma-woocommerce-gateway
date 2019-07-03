<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class Alma_WC_Payment {

	public static function from_cart() {
		$cart     = new Alma_WC_Cart();
		$customer = new Alma_WC_Customer();

		$data = array(
			"payment" => array(
				"purchase_amount"  => $cart->get_total(),
				'return_url'       => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::CustomerReturn ),
				'ipn_callback_url' => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::IpnCallback ),
			),
		);

		if ( $customer->has_data() ) {
			$data['payment']['billing_address']  = $customer->get_billing_address();
			$data['payment']['shipping_address'] = $customer->get_shipping_address();
			$data['customer']                    = $customer->get_data();
		}

		return $data;
	}

	public static function from_order( $order_id, $installments_count ) {
		try {
			$order = new Alma_WC_Order( $order_id );
		} catch ( Exception $e ) {
		    $logger = new Alma_WC_Logger();
			$logger->error( "Error getting payment info from order: " . $e->getMessage() );

			return array();
		}

		$data = array(
			'payment' => array(
				'purchase_amount'  => $order->get_total(),
				'return_url'       => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::CustomerReturn ),
                'ipn_callback_url' => Alma_WC_Webhooks::url_for( Alma_WC_Webhooks::IpnCallback ),
				'installments_count' => $installments_count,
				'custom_data'      => array(
					'order_id'  => $order_id,
					'order_key' => $order->get_order_key()
				),
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

		// Merge built data on data extracted from Cart to have as much data as possible
		$data = alma_wc_array_merge_recursive( self::from_cart(), $data );

		return $data;
	}
}
