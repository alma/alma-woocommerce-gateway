<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Domain\OrderInterface;
use Alma\API\DTO\AddressDto;
use Alma\API\DTO\CustomerDto;

class CustomerMapper {

	/**
	 * Builds a CustomerDto from an Order.
	 *
	 * @param OrderInterface $order The Order object.
	 *
	 * @return CustomerDto The constructed CustomerDto.
	 */
	public function buildCustomerDto( OrderInterface $order ): CustomerDto {

		$customerDto = ( new CustomerDto() )
			->setFirstName( $order->getBillingFirstName() )
			->setLastName( $order->getBillingLastName() )
			->setEmail( $order->getBillingEmail() )
			->setPhone( $order->getBillingPhone() );

		if ( $order->getBillingCompany() ) {
			$customerDto->setIsBusiness( true );
			$customerDto->setBusinessName( $order->getBillingCompany() );
		}
		if ( $order->hasBillingAddress() ) {
			$customerDto->addAddress(
				( new AddressDto() )
					->setFirstName( $order->getBillingFirstName() )
					->setLastName( $order->getBillingLastName() )
					->setCompany( $order->getBillingCompany() )
					->setLine1( $order->getBillingAddress1() )
					->setLine2( $order->getBillingAddress2() )
					->setPostalCode( $order->getBillingPostcode() )
					->setCity( $order->getBillingCity() )
					->setCountry( $order->getBillingCountry() )
					->setEmail( $order->getBillingEmail() )
			);
		}
		if ( $order->hasShippingAddress() ) {
			$customerDto->addAddress(
				( new AddressDto() )
					->setFirstName( $order->getShippingFirstName() )
					->setLastName( $order->getShippingLastName() )
					->setCompany( $order->getShippingCompany() )
					->setLine1( $order->getShippingAddress1() )
					->setLine2( $order->getShippingAddress2() )
					->setPostalCode( $order->getShippingPostcode() )
					->setCity( $order->getShippingCity() )
					->setCountry( $order->getShippingCountry() )
					->setEmail( $order->getShippingEmail() )
			);
		}

		return $customerDto;
	}
}
