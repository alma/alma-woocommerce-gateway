<?php
/**
 * Alma_Customer.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/models
 * @namespace Alma\Woocommerce\Models
 */

namespace Alma\Woocommerce\Models;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Customer
 */
class Alma_Customer {

    /**
     * Customer.
     *
     * @var \WC_Customer|null
     */
	protected $customer;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
        $this->customer = wc()->customer;
	}

	/**
	 * Get data.
	 *
	 * @return array
	 */
	public function get_data() {
        $data = array(
            'first_name' => $this->customer->get_first_name(),
            'last_name'  => $this->customer->get_last_name(),
            'email'      => $this->customer->get_email(),
            'phone'      => $this->customer->get_billing_phone(),
        );

        foreach ( array( 'first_name', 'last_name', 'email', 'phone' ) as $attr ) {
            $method = "get_billing_$attr";
            if ( empty( $data[ $attr ] ) && method_exists( $this->customer, $method ) ) {
                $data[ $attr ] = $this->customer->$method();
            }

            $method = "get_shipping_$attr";
            if ( empty( $data[ $attr ] ) && method_exists( $this->customer, $method ) ) {
                $data[ $attr ] = $this->customer->$method();
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
			'first_name'  => $this->customer->get_billing_first_name(),
			'last_name'   => $this->customer->get_billing_last_name(),
			'line1'       => $this->customer->get_billing_address(),
			'line2'       => $this->customer->get_billing_address_2(),
			'postal_code' => $this->customer->get_billing_postcode(),
			'city'        => $this->customer->get_billing_city(),
			'country'     => $this->customer->get_billing_country(),
			'email'       => $this->customer->get_billing_email(),
			'phone'       => $this->customer->get_billing_phone(),
		);
	}

	/**
	 * Gets billing country if customer exists.
	 *
	 * @return string|null
	 */
	public function get_billing_country() {
		if ( $this->customer ) {
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
            'first_name'  => $this->customer->get_shipping_first_name(),
            'last_name'   => $this->customer->get_shipping_last_name(),
            'line1'       => $this->customer->get_shipping_address(),
            'line2'       => $this->customer->get_shipping_address_2(),
            'postal_code' => $this->customer->get_shipping_postcode(),
            'city'        => $this->customer->get_shipping_city(),
            'country'     => $this->customer->get_shipping_country(),
        );
	}

	/**
	 * Gets shipping country if customer exists.
	 *
	 * @return string|null
	 */
	public function get_shipping_country() {
		if ( $this->customer ) {
			return $this->get_shipping_address()['country'];
		}

		return null;
	}
}
