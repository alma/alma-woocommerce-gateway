<?php

namespace Alma\Gateway\Application\Mapper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\AddressDto;
use Alma\Client\Application\DTO\EligibilityDto;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\CustomerAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class EligibilityMapper {

	/** @var LoggerInterface|null */
	private $logger;

	/**
	 * @param LoggerInterface|null $logger Optional logger, used to report skipped fee plans.
	 */
	public function __construct( ?LoggerInterface $logger = null ) {
		$this->logger = $logger;
	}

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

		// Add queries to EligibilityDto. A single misconfigured fee plan must
		// not disable every Alma gateway at checkout (#617): the client DTO
		// validates each query, so skip the broken plan and keep the others
		// queryable instead of aborting the whole eligibility call.
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			try {
				$eligibilityDto->addQuery( ( new EligibilityQueryMapper() )->buildEligibilityQueryDto( $feePlanAdapter ) );
			} catch ( InvalidArgumentException $exception ) {
				if ( null !== $this->logger ) {
					$this->logger->warning(
						'Skipped an invalid fee plan when building the eligibility query.',
						array( 'error' => $exception->getMessage() )
					);
				}
			}
		}

		return $eligibilityDto;
	}
}
