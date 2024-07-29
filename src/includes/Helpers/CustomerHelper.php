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

		$array_keys = array_keys( $data );

		foreach ( $array_keys as $attr ) {
			$method = "get_billing_$attr";
			$data   = $this->get_customer_data( $method, $data, $attr );

			$method = "get_shipping_$attr";
			$data   = $this->get_customer_data( $method, $data, $attr );
		}

		$data['addresses'] = array(
			$this->get_billing_address(),
			$this->get_shipping_address(),
		);

		return $data;
	}

	/**
	 * Get customer data.
	 *
	 * @param string $method The method.
	 * @param array  $data The array.
	 * @param string $attr The field name.
	 *
	 * @return mixed
	 */
	public function get_customer_data( $method, $data, $attr ) {

		if (
			empty( $data[ $attr ] )
		) {
			$result = $this->customer_factory->call_method( $method );

			if ( false !== $result ) {
				$data[ $attr ] = $result;
			}
		}

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
			'country'     => $this->customer_factory->get_shipping_country(),
		);
	}
}
