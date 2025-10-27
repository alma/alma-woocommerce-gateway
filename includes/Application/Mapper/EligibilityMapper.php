<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\AddressDto;
use Alma\API\Application\DTO\EligibilityDto;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\CustomerAdapter;

class EligibilityMapper {

	/**
	 * Builds an EligibilityDto from a CartAdapter and a CustomerAdapter.
	 *
	 * @param CartAdapter     $cartAdapter The cart adapter.
	 * @param CustomerAdapter $customerAdapter The customer adapter.
	 *
	 * @return EligibilityDto The constructed EligibilityDto.
	 */
	public function buildEligibilityDto( CartAdapter $cartAdapter, CustomerAdapter $customerAdapter ): EligibilityDto {

		$billingAddress = ( new AddressDto() )
			->setFirstName( $customerAdapter->getCustomerBillingAddress()->getFirstName() )
			->setLastName( $customerAdapter->getCustomerBillingAddress()->getLastName() )
			->setCompany( $customerAdapter->getCustomerBillingAddress()->getCompany() )
			->setLine1( $customerAdapter->getCustomerBillingAddress()->getLine1() )
			->setLine2( $customerAdapter->getCustomerBillingAddress()->getLine2() )
			->setPostalCode( $customerAdapter->getCustomerBillingAddress()->getPostalCode() )
			->setCity( $customerAdapter->getCustomerBillingAddress()->getCity() )
			->setStateProvince( $customerAdapter->getCustomerBillingAddress()->getStateProvince() )
			->setCountry( $customerAdapter->getCustomerBillingAddress()->getCountry() );
		if ( ! empty( $customerAdapter->getCustomerBillingAddress()->getEmail() ) ) {
			$billingAddress->setEmail( $customerAdapter->getCustomerBillingAddress()->getEmail() );
		}

		$shippingAddress = ( new AddressDto() )
			->setFirstName( $customerAdapter->getCustomerShippingAddress()->getFirstName() )
			->setLastName( $customerAdapter->getCustomerShippingAddress()->getLastName() )
			->setCompany( $customerAdapter->getCustomerShippingAddress()->getCompany() )
			->setLine1( $customerAdapter->getCustomerShippingAddress()->getLine1() )
			->setLine2( $customerAdapter->getCustomerShippingAddress()->getLine2() )
			->setPostalCode( $customerAdapter->getCustomerShippingAddress()->getPostalCode() )
			->setCity( $customerAdapter->getCustomerShippingAddress()->getCity() )
			->setStateProvince( $customerAdapter->getCustomerShippingAddress()->getStateProvince() )
			->setCountry( $customerAdapter->getCustomerShippingAddress()->getCountry() );

		return ( new EligibilityDto( $cartAdapter->getCartTotal() ) )
			->setBillingAddress( $billingAddress )
			->setShippingAddress( $shippingAddress );
	}
}
