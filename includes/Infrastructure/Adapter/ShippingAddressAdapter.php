<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\Plugin\Infrastructure\Adapter\ShippingAddressAdapterInterface;
use WC_Customer;

class ShippingAddressAdapter implements ShippingAddressAdapterInterface {

	private WC_Customer $customer;

	public function __construct( WC_Customer $customer ) {
		$this->customer = $customer;
	}

	/**
	 * Get the first name
	 * @return string The first name.
	 */
	public function getFirstName(): string {
		return $this->customer->get_shipping_first_name();
	}

	/**
	 * Get the last name
	 * @return string The last name.
	 */
	public function getLastName(): string {
		return $this->customer->get_shipping_last_name();
	}

	/**
	 * Get the company
	 * @return string The company.
	 */
	public function getCompany(): string {
		return $this->customer->get_shipping_company();
	}

	/**
	 * Get the first line of the address
	 * @return string The first line of the address.
	 */
	public function getLine1(): string {
		return $this->customer->get_shipping_address_1();
	}

	/**
	 * Get the second line of the address
	 * @return string The second line of the address.
	 */
	public function getLine2(): string {
		return $this->customer->get_shipping_address_2();
	}

	/**
	 * Get the postal code
	 * @return string The postal code.
	 */
	public function getPostalCode(): string {
		return $this->customer->get_shipping_postcode();
	}

	/**
	 * Get the city
	 * @return string The city.
	 */
	public function getCity(): string {
		return $this->customer->get_shipping_city();
	}

	/**
	 * Get the state/province
	 * @return string The state/province.
	 */
	public function getStateProvince(): string {
		return $this->customer->get_shipping_state();
	}

	/**
	 * Get the country
	 * @return string The country.
	 */
	public function getCountry(): string {
		return $this->customer->get_shipping_country();
	}
}
