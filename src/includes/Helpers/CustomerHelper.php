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

use Alma\Woocommerce\Factories\CustomerFactory;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CustomerHelper.
 */
class CustomerHelper {

	/**
	 * Customer Factory.
	 *
	 * @var CustomerFactory The customer factory.
	 */
	protected $customer_factory;
	/**
	 * Construct.
	 *
	 * @param CustomerFactory $customer_factory The customer factory.
	 */
	public function __construct( $customer_factory ) {
		$this->customer_factory = $customer_factory;
	}
	/**
	 * Get data.
	 *
	 * @return array
	 */
	public function get_data() {
		$data = array(
			'first_name' => $this->customer_factory->get_first_name(),
			'last_name'  => $this->customer_factory->get_last_name(),
			'email'      => $this->customer_factory->get_email(),
			'phone'      => $this->customer_factory->get_billing_phone(),
		);

		foreach ( array( 'first_name', 'last_name', 'email', 'phone' ) as $attr ) {
			$method = "get_billing_$attr";
			if ( empty( $data[ $attr ] ) && method_exists( $this->customer_factory->get_customer(), $method ) ) {
				$data[ $attr ] = $this->customer_factory->get_customer()->$method();
			}

			$method = "get_shipping_$attr";
			if ( empty( $data[ $attr ] ) && method_exists( $this->customer_factory->get_customer(), $method ) ) {
				$data[ $attr ] = $this->customer_factory->get_customer()->$method();
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
			'first_name'  => $this->customer_factory->get_billing_first_name(),
			'last_name'   => $this->customer_factory->get_billing_last_name(),
			'line1'       => $this->customer_factory->get_billing_address(),
			'line2'       => $this->customer_factory->get_billing_address_2(),
			'postal_code' => $this->customer_factory->get_billing_postcode(),
			'city'        => $this->customer_factory->get_billing_city(),
			'country'     => $this->customer_factory->get_billing_country(),
			'email'       => $this->customer_factory->get_billing_email(),
			'phone'       => $this->customer_factory->get_billing_phone(),
		);
	}

	/**
	 * Gets billing country if customer exists.
	 *
	 * @return string|null
	 */
	public function get_billing_country() {
		if ( $this->customer_factory->get_customer() ) {
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
			'first_name'  => $this->customer_factory->get_shipping_first_name(),
			'last_name'   => $this->customer_factory->get_shipping_last_name(),
			'line1'       => $this->customer_factory->get_shipping_address(),
			'line2'       => $this->customer_factory->get_shipping_address_2(),
			'postal_code' => $this->customer_factory->get_shipping_postcode(),
			'city'        => $this->customer_factory->get_shipping_city(),
			'country'     => $this->customer_factory->get_customer()->get_shipping_country(),
		);
	}

	/**
	 * Gets shipping country if customer exists.
	 *
	 * @return string|null
	 */
	public function get_shipping_country() {
		if ( $this->customer_factory->get_customer() ) {
			return $this->get_shipping_address()['country'];
		}

		return null;
	}
}
