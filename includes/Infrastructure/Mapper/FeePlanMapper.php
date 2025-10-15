<?php

namespace Alma\Gateway\Infrastructure\Mapper;

use Alma\API\Domain\Adapter\FeePlanInterface;
use Alma\Gateway\Infrastructure\Dto\FeePlanDto;

class FeePlanMapper {

	public function buildFeePlanDto( feePlanInterface $feePlanAdapter ): FeePlanDto {

		return new FeePlanDto(
			$feePlanAdapter->getInstallmentsCount(),
			$feePlanAdapter->getMinPurchaseAmount(),
			$feePlanAdapter->getMaxPurchaseAmount(),
			$feePlanAdapter->getDeferredDays(),
			$feePlanAdapter->getDeferredMonths(),
		);
	}
}
