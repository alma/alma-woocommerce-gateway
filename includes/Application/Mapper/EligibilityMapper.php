<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\AddressDto;
use Alma\API\Application\DTO\EligibilityDto;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\CustomerAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;

class EligibilityMapper {

	/**
	 * Builds an EligibilityDto from a CartAdapter and a CustomerAdapter.
	 *
	 * @param CartAdapter        $cartAdapter The cart adapter.
	 * @param CustomerAdapter    $customerAdapter The customer adapter.
	 * @param FeePlanListAdapter $feePlanListAdapter The fee plan list adapter to filter eligibilities.
	 *
	 * @return EligibilityDto The constructed EligibilityDto.
	 */
	public function buildEligibilityDto( CartAdapter $cartAdapter, CustomerAdapter $customerAdapter, FeePlanListAdapter $feePlanListAdapter ): EligibilityDto {

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

		$eligibilityDto = ( new EligibilityDto( $cartAdapter->getCartTotal() ) )
			->setBillingAddress( $billingAddressDto )
			->setShippingAddress( $shippingAddressDto );

		// Add queries to EligibilityDto
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			$a = ( new EligibilityQueryMapper() )->buildEligibilityQueryDto( $feePlanAdapter );
			almalog( 'Adding eligibility query for fee plan: ', var_export( $a, true ) );
			$eligibilityDto->addQuery( ( new EligibilityQueryMapper() )->buildEligibilityQueryDto( $feePlanAdapter ) );
		}

		return $eligibilityDto;
	}
}
