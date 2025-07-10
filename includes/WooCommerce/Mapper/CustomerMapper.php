<?php

namespace Alma\Gateway\WooCommerce\Mapper;

use Alma\API\Entities\DTO\AddressDto;
use Alma\API\Entities\DTO\CustomerDto;
use WC_Order;

class CustomerMapper {

	/**
	 * Builds a CustomerDto from a WooCommerce order.
	 *
	 * @param WC_Order $wc_order The WooCommerce order object.
	 *
	 * @return CustomerDto The constructed CustomerDto.
	 */
	public function build_customer_dto( WC_Order $wc_order ): CustomerDto {

		$customer_dto = ( new CustomerDto() )
			->setFirstName( $wc_order->get_billing_first_name() )
			->setLastName( $wc_order->get_billing_last_name() )
			->setEmail( $wc_order->get_billing_email() )
			->setPhone( $wc_order->get_billing_phone() );

		if ( $wc_order->get_billing_company() ) {
			$customer_dto->setIsBusiness( true );
			$customer_dto->setBusinessName( $wc_order->get_billing_company() );
		}
		if ( $wc_order->has_billing_address() ) {
			$customer_dto->addAddress(
				( new AddressDto() )
					->setFirstName( $wc_order->get_billing_first_name() )
					->setLastName( $wc_order->get_billing_last_name() )
					->setCompany( $wc_order->get_billing_company() )
					->setLine1( $wc_order->get_billing_address_1() )
					->setLine2( $wc_order->get_billing_address_2() )
					->setPostalCode( $wc_order->get_billing_postcode() )
					->setCity( $wc_order->get_billing_city() )
					->setCountry( $wc_order->get_billing_country() )
					->setEmail( $wc_order->get_billing_email() )
			);
		}
		if ( $wc_order->has_shipping_address() ) {
			$customer_dto->addAddress(
				( new AddressDto() )
					->setFirstName( $wc_order->get_shipping_first_name() )
					->setLastName( $wc_order->get_shipping_last_name() )
					->setCompany( $wc_order->get_shipping_company() )
					->setLine1( $wc_order->get_shipping_address_1() )
					->setLine2( $wc_order->get_shipping_address_2() )
					->setPostalCode( $wc_order->get_shipping_postcode() )
					->setCity( $wc_order->get_shipping_city() )
					->setCountry( $wc_order->get_shipping_country() )
			);
		}

		return $customer_dto;
	}
}
