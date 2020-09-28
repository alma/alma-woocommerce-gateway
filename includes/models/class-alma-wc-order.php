<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class Alma_WC_Order {
	private $legacy = false;
	private $order;

	/**
	 * Alma_Order constructor.
	 *
	 * @param $order_id
	 * @param null     $order_key
	 *
	 * @throws Exception
	 */
	public function __construct( $order_id, $order_key = null ) {
		$this->legacy = version_compare( wc()->version, '3.0.0', '<' );

		$order = wc_get_order( $order_id );

		if ( ! $order && $order_key ) {
			// We have an invalid $order_id, probably because invoice_prefix has changed.
			$order_id = wc_get_order_id_by_order_key( $order_key );
			$order    = wc_get_order( $order_id );
		}

		if ( ! $order || ( $order_key && $order->get_order_key() !== $order_key ) ) {
			throw new Exception( "Can't find order '$order_id' (key: $order_key). Order Keys do not match." );
		}

		$this->order = $order;
	}

	public function payment_complete( $payment_id ) {
		$this->order->payment_complete( $payment_id );
		wc()->cart->empty_cart();
	}

	public function get_wc_order() {
		return $this->order;
	}

	public function get_total() {
		return alma_wc_price_to_cents( $this->order->get_total() );
	}

	public function get_id() {
		return $this->order->get_id();
	}

	public function get_order_key() {
		if ( $this->legacy ) {
			return $this->order->order_key;
		} else {
			return $this->order->get_order_key();
		}
	}

	public function get_order_reference() {
		return $this->order->get_order_number();
	}

	public function has_billing_address() {
		if ( $this->legacy ) {
			return $this->order->billing_address_1 || $this->order->billing_address_2;
		} else {
			return $this->order->get_billing_address_1() || $this->order->get_billing_address_2();
		}
	}

	public function has_shipping_address() {
		if ( $this->legacy ) {
			return $this->order->shipping_address_1 || $this->order->shipping_address_2;
		} else {
			return $this->order->get_shipping_address_1() || $this->order->get_shipping_address_2();
		}
	}

	public function get_billing_address() {
		if ( $this->legacy ) {
			return array(
				'first_name'  => $this->order->billing_first_name,
				'last_name'   => $this->order->billing_last_name,
				'company'     => $this->order->billing_company,
				'line1'       => $this->order->billing_address_1,
				'line2'       => $this->order->billing_address_2,
				'postal_code' => $this->order->billing_postcode,
				'city'        => $this->order->billing_city,
				'country'     => $this->order->billing_country,
				'email'       => $this->order->billing_email,
				'phone'       => $this->order->billing_phone,
			);
		} else {
			return array(
				'first_name'  => $this->order->get_billing_first_name(),
				'last_name'   => $this->order->get_billing_last_name(),
				'company'     => $this->order->get_billing_company(),
				'line1'       => $this->order->get_billing_address_1(),
				'line2'       => $this->order->get_billing_address_2(),
				'postal_code' => $this->order->get_billing_postcode(),
				'city'        => $this->order->get_billing_city(),
				'country'     => $this->order->get_billing_country(),
				'email'       => $this->order->get_billing_email(),
				'phone'       => $this->order->get_billing_phone(),
			);
		}
	}

	public function get_shipping_address() {
		if ( $this->legacy ) {
			return array(
				'first_name'  => $this->order->shipping_first_name,
				'last_name'   => $this->order->shipping_last_name,
				'company'     => $this->order->shipping_company,
				'line1'       => $this->order->shipping_address_1,
				'line2'       => $this->order->shipping_address_2,
				'postal_code' => $this->order->shipping_postcode,
				'city'        => $this->order->shipping_city,
				'country'     => $this->order->shipping_country,
			);
		} else {
			return array(
				'first_name'  => $this->order->get_shipping_first_name(),
				'last_name'   => $this->order->get_shipping_last_name(),
				'company'     => $this->order->get_shipping_company(),
				'line1'       => $this->order->get_shipping_address_1(),
				'line2'       => $this->order->get_shipping_address_2(),
				'postal_code' => $this->order->get_shipping_postcode(),
				'city'        => $this->order->get_shipping_city(),
				'country'     => $this->order->get_shipping_country(),
			);
		}
	}

	public function get_customer_url() {
		return $this->order->get_view_order_url();
	}

	public function get_merchant_url() {
		$admin_path = 'post.php?post=' . $this->order->get_id() . '&action=edit';

		if ( version_compare( wc()->version, '2.6.0', '<' ) ) {
			return '';
		} elseif ( version_compare( wc()->version, '3.0.0', '<' ) ) {
			return admin_url( $admin_path );
		} elseif ( version_compare( wc()->version, '3.3.0', '<' ) ) {
			return get_admin_url( null, $admin_path );
		} else {
			return $this->order->get_edit_order_url();
		}
	}
}
