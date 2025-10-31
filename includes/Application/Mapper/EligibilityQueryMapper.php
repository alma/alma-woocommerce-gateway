<?php

namespace Alma\Gateway\Application\Mapper;

use Alma\API\Application\DTO\EligibilityQueryDto;
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
