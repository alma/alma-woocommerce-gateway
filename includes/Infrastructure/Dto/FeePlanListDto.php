<?php

namespace Alma\Gateway\Infrastructure\Dto;

use Alma\API\Application\DTO\DtoInterface;

class FeePlanListDto implements DtoInterface {
	private array $feePlans = array();

	public function addFeePlan( FeePlanDto $feePlan ): self {
		$this->feePlans[] = $feePlan->toArray();

		return $this;
	}

	public function toArray(): array {
		return array( 'plans' => $this->feePlans );
	}
}
