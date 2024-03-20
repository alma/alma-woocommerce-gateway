<?php
/**
 * CustomerHelper.
 *
 * @since 4.?
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CustomerHelper.
 */
class CustomerHelper {

	/**
	 * Get data.
	 *
	 * @return array
	 */
	public function get_data() {
		$data = array(
			'first_name' => wc()->customer->get_first_name(),
			'last_name'  => wc()->customer->get_last_name(),
			'email'      => wc()->customer->get_email(),
			'phone'      => wc()->customer->get_billing_phone(),
		);

		foreach ( array( 'first_name', 'last_name', 'email', 'phone' ) as $attr ) {
			$method = "get_billing_$attr";
			if ( empty( $data[ $attr ] ) && method_exists( wc()->customer, $method ) ) {
				$data[ $attr ] = wc()->customer->$method();
			}

			$method = "get_shipping_$attr";
			if ( empty( $data[ $attr ] ) && method_exists( wc()->customer, $method ) ) {
				$data[ $attr ] = wc()->customer->$method();
			}
		}

		$data['addresses'] = array(
			$this->get_billing_address(),
			$this->get_shipping_address(),
		);

		return $data;
	}

	/**
	 * Get billing address.
	 *
	 * @return array
	 */
	public function get_billing_address() {
		return array(
			'first_name'  => wc()->customer->get_billing_first_name(),
			'last_name'   => wc()->customer->get_billing_last_name(),
			'line1'       => wc()->customer->get_billing_address(),
			'line2'       => wc()->customer->get_billing_address_2(),
			'postal_code' => wc()->customer->get_billing_postcode(),
			'city'        => wc()->customer->get_billing_city(),
			'country'     => wc()->customer->get_billing_country(),
			'email'       => wc()->customer->get_billing_email(),
			'phone'       => wc()->customer->get_billing_phone(),
		);
	}

	/**
	 * Gets billing country if customer exists.
	 *
	 * @return string|null
	 */
	public function get_billing_country() {
		if ( wc()->customer ) {
			return $this->get_billing_address()['country'];
		}

		return null;
	}

	/**
	 * Gets shipping address.
	 *
	 * @return array
	 */
	public function get_shipping_address() {
		return array(
			'first_name'  => wc()->customer->get_shipping_first_name(),
			'last_name'   => wc()->customer->get_shipping_last_name(),
			'line1'       => wc()->customer->get_shipping_address(),
			'line2'       => wc()->customer->get_shipping_address_2(),
			'postal_code' => wc()->customer->get_shipping_postcode(),
			'city'        => wc()->customer->get_shipping_city(),
			'country'     => wc()->customer->get_shipping_country(),
		);
	}

	/**
	 * Gets shipping country if customer exists.
	 *
	 * @return string|null
	 */
	public function get_shipping_country() {
		if ( wc()->customer ) {
			return $this->get_shipping_address()['country'];
		}

		return null;
	}
}
