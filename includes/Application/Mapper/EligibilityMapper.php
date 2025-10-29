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

		$customerBillingAddress  = $customerAdapter->getCustomerBillingAddress();
		$customerShippingAddress = $customerAdapter->getCustomerShippingAddress();

		$billingAddressDto = new AddressDto();
		if ( $customerBillingAddress ) {
			$billingAddressDto
				->setFirstName( $customerBillingAddress->getFirstName() )
				->setLastName( $customerBillingAddress->getLastName() )
				->setCompany( $customerBillingAddress->getCompany() )
				->setLine1( $customerBillingAddress->getLine1() )
				->setLine2( $customerBillingAddress->getLine2() )
				->setPostalCode( $customerBillingAddress->getPostalCode() )
				->setCity( $customerBillingAddress->getCity() )
				->setStateProvince( $customerBillingAddress->getStateProvince() )
				->setCountry( $customerBillingAddress->getCountry() );
			if ( ! empty( $customerBillingAddress->getEmail() ) ) {
				$billingAddressDto->setEmail( $customerBillingAddress->getEmail() );
			}
		}

		$shippingAddressDto = new AddressDto();
		if ( $customerShippingAddress ) {
			$shippingAddressDto
				->setFirstName( $customerShippingAddress->getFirstName() )
				->setLastName( $customerShippingAddress->getLastName() )
				->setCompany( $customerShippingAddress->getCompany() )
				->setLine1( $customerShippingAddress->getLine1() )
				->setLine2( $customerShippingAddress->getLine2() )
				->setPostalCode( $customerShippingAddress->getPostalCode() )
				->setCity( $customerShippingAddress->getCity() )
				->setStateProvince( $customerShippingAddress->getStateProvince() )
				->setCountry( $customerShippingAddress->getCountry() );
		}

		return ( new EligibilityDto( $cartAdapter->getCartTotal() ) )
			->setBillingAddress( $billingAddressDto )
			->setShippingAddress( $shippingAddressDto );
	}
}
