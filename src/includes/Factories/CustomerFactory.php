<?php
/**
 * CustomerFactory.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Factories
 * @namespace Alma\Woocommerce\Factories
 */

namespace Alma\Woocommerce\Factories;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CustomerFactory.
 */
class CustomerFactory {

	/**
	 * The php factory.
	 *
	 * @var PHPFactory
	 */
	protected $php_factory;

	/**
	 * Construct.
	 *
	 * @param PHPFactory $php_factory The php factory.
	 */
	public function __construct( $php_factory ) {
		$this->php_factory = $php_factory;
	}
	/**
	 * Get the customer.
	 *
	 * @return \WC_Customer|null
	 */
	public function get_customer() {
		return wc()->customer;
	}

	/**
	 * Get the first name.
	 *
	 * @return string|null The first name.
	 */
	public function get_first_name() {
		$customer = $this->get_customer();

		if ( ! $customer ) {
			return null;
		}

		return $customer->get_first_name();
	}

	/**
	 * Get the last name.
	 *
	 * @return string|null The last name.
	 */
	public function get_last_name() {
		$customer = $this->get_customer();

		if ( ! $customer ) {
			return null;
		}

		return $customer->get_last_name();
	}

	/**
	 * Get the email.
	 *
	 * @return string|null The email.
	 */
	public function get_email() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_email();
	}

	/**
	 * Get the billing phone.
	 *
	 * @return string|null The billing phone.
	 */
	public function get_billing_phone() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_phone();
	}

	/**
	 * Get the billing first name.
	 *
	 * @return string|null The billing first name.
	 */
	public function get_billing_first_name() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_first_name();
	}

	/**
	 * Get the shipping first name.
	 *
	 * @return string|null The shipping first name.
	 */
	public function get_shipping_first_name() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_first_name();
	}

	/**
	 * Get the billing last name.
	 *
	 * @return string|null The billing last name.
	 */
	public function get_billing_last_name() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_last_name();
	}

	/**
	 * Get the shipping last name.
	 *
	 * @return string|null The shipping last name.
	 */
	public function get_shipping_last_name() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_last_name();
	}

	/**
	 * Get the billing address.
	 *
	 * @return string|null The billing address.
	 */
	public function get_billing_address() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_address();
	}

	/**
	 * Get the shipping address.
	 *
	 * @return string|null The shipping address.
	 */
	public function get_shipping_address() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_address();
	}


	/**
	 * Get the billing address 2 .
	 *
	 * @return string|null The billing address 2.
	 */
	public function get_billing_address_2() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_address_2();
	}


	/**
	 * Get the billing shipping 2 .
	 *
	 * @return string|null The shipping address 2.
	 */
	public function get_shipping_address_2() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_address_2();
	}

	/**
	 *
	 * Get the billing post code.
	 *
	 * @return string|null The billing post code.
	 */
	public function get_billing_postcode() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_postcode();
	}


	/**
	 *
	 * Get the shipping post code.
	 *
	 * @return string|null The shipping post code.
	 */
	public function get_shipping_postcode() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_postcode();
	}


	/**
	 *
	 * Get the billing city.
	 *
	 * @return string|null The billing city.
	 */
	public function get_billing_city() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_city();
	}


	/**
	 *
	 * Get the shipping city.
	 *
	 * @return string|null The shipping city.
	 */
	public function get_shipping_city() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_city();
	}

	/**
	 *
	 * Get the billing country.
	 *
	 * @return string|null The billing country.
	 */
	public function get_billing_country() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_country();
	}

	/**
	 *
	 * Get the shipping country.
	 *
	 * @return string|null The billing country.
	 */
	public function get_shipping_country() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_shipping_country();
	}

	/**
	 *
	 * Get the billing email.
	 *
	 * @return string|null The billing email.
	 */
	public function get_billing_email() {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return null;
		}

		return $customer->get_billing_email();
	}

	/**
	 * Call a method.
	 *
	 * @param string $method The method.
	 *
	 * @return null|string|false
	 */
	public function call_method( $method ) {
		$customer = $this->get_customer();
		if ( ! $customer ) {
			return false;
		}

		if ( $this->php_factory->method_exists( $customer, $method ) ) {
			return $customer->$method();
		}

		return false;
	}

}
