<?php

namespace Alma\Gateway\Infrastructure\Mapper;

use Alma\Gateway\Infrastructure\Dto\FeePlanDto;
use Alma\Plugin\Infrastructure\Adapter\FeePlanInterface;

class FeePlanMapper {

	public function buildFeePlanDto( feePlanInterface $feePlanAdapter ): FeePlanDto {

		return new FeePlanDto(
			$feePlanAdapter->getInstallmentsCount(),
			$feePlanAdapter->getOverrideMinPurchaseAmount(),
			$feePlanAdapter->getOverrideMaxPurchaseAmount(),
			$feePlanAdapter->getDeferredDays(),
			$feePlanAdapter->getDeferredMonths(),
		);
	}
}
