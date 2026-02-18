<?php

namespace Alma\Gateway\Infrastructure\Dto;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\DtoInterface;

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
