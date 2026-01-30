<?php

namespace Alma\Gateway\Infrastructure\Mapper;

use Alma\Gateway\Infrastructure\Dto\FeePlanListDto;
use Alma\Plugin\Infrastructure\Adapter\FeePlanListAdapterInterface;

class FeePlanListMapper {

	/**
	 * Builds an FeePlanListDto from an FeePlanListAdapter.
	 *
	 * @param feePlanListAdapterInterface $feePlanListAdapter
	 *
	 * @return FeePlanListDto The constructed FeePlanListDto.
	 */
	public function buildFeePlanListDto( feePlanListAdapterInterface $feePlanListAdapter ): FeePlanListDto {
		$feePlanListDto = new FeePlanListDto();
		foreach ( $feePlanListAdapter as $feePlanAdapter ) {
			$feePlanListDto->addFeePlan(
				( new FeePlanMapper() )->buildFeePlanDto( $feePlanAdapter )
			);
		}

		return $feePlanListDto;
	}
}
