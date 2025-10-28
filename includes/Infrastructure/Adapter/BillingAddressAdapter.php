<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\BillingAddressAdapterInterface;
use WC_Customer;

class BillingAddressAdapter implements BillingAddressAdapterInterface {

	private WC_Customer $customer;

	public function __construct( WC_Customer $customer ) {
		$this->customer = $customer;
	}

	public function getFirstName(): string {
		return $this->customer->get_billing_first_name();
	}

	public function getLastName(): string {
		return $this->customer->get_billing_last_name();
	}

	public function getCompany(): string {
		return $this->customer->get_billing_company();
	}

	public function getLine1(): string {
		return $this->customer->get_billing_address_1();
	}

	public function getLine2(): string {
		return $this->customer->get_billing_address_2();
	}

	public function getPostalCode(): string {
		return $this->customer->get_billing_postcode();
	}

	public function getCity(): string {
		return $this->customer->get_billing_city();
	}

	public function getStateProvince(): string {
		return $this->customer->get_billing_state();
	}

	public function getCountry(): string {
		return $this->customer->get_billing_country();
	}

	public function getEmail(): string {
		return $this->customer->get_billing_email();
	}
}
