<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\ShippingAddressAdapterInterface;
use WC_Customer;

class ShippingAddressAdapter implements ShippingAddressAdapterInterface {

	private WC_Customer $customer;

	public function __construct( WC_Customer $customer ) {
		$this->customer = $customer;
	}

	public function getFirstName(): string {
		return $this->customer->get_shipping_first_name();
	}

	public function getLastName(): string {
		return $this->customer->get_shipping_last_name();
	}

	public function getCompany(): string {
		return $this->customer->get_shipping_company();
	}

	public function getLine1(): string {
		return $this->customer->get_shipping_address_1();
	}

	public function getLine2(): string {
		return $this->customer->get_shipping_address_2();
	}

	public function getPostalCode(): string {
		return $this->customer->get_shipping_postcode();
	}

	public function getCity(): string {
		return $this->customer->get_shipping_city();
	}

	public function getStateProvince(): string {
		return $this->customer->get_shipping_state();
	}

	public function getCountry(): string {
		return $this->customer->get_shipping_country();
	}
}
