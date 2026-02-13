<?php

namespace Alma\Gateway\Application\Mapper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\EligibilityQueryDto;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;

class EligibilityQueryMapper {

	/**
	 * Builds an EligibilityDto from a CartAdapter and a CustomerAdapter.
	 *
	 * @param FeePlanAdapter $feePlanAdapter
	 *
	 * @return EligibilityQueryDto The constructed EligibilityDto.
	 */
	public function buildEligibilityQueryDto( FeePlanAdapter $feePlanAdapter ): EligibilityQueryDto {

		return ( new EligibilityQueryDto( $feePlanAdapter->getInstallmentsCount() ) )
			->setDeferredDays( $feePlanAdapter->getDeferredDays() )
			->setDeferredMonths( $feePlanAdapter->getDeferredMonths() );
	}
}
