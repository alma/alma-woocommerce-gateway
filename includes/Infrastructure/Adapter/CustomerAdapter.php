<?php

namespace Alma\Gateway\Infrastructure\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Plugin\Infrastructure\Adapter\BillingAddressAdapterInterface;
use Alma\Plugin\Infrastructure\Adapter\CustomerAdapterInterface;
use Alma\Plugin\Infrastructure\Adapter\ShippingAddressAdapterInterface;
use WC_Customer;

class CustomerAdapter implements CustomerAdapterInterface {

	private ?WC_Customer $customer = null;

	public function __construct( ?WC_Customer $customer ) {
		$this->customer = $customer;
	}

	/**
	 * Get the customer's shipping address.
	 * @return ShippingAddressAdapterInterface|null
	 */
	public function getCustomerShippingAddress(): ?ShippingAddressAdapterInterface {
		if ( ! $this->customer ) {
			return null;
		}

		return new ShippingAddressAdapter( $this->customer );
	}

	/**
	 * Get the customer's billing address.
	 * @return BillingAddressAdapterInterface|null
	 */
	public function getCustomerBillingAddress(): ?BillingAddressAdapterInterface {
		if ( ! $this->customer ) {
			return null;
		}

		return new BillingAddressAdapter( $this->customer );
	}
}
