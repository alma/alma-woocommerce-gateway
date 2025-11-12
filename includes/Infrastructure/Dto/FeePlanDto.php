<?php

namespace Alma\Gateway\Infrastructure\Dto;

use Alma\API\Application\DTO\DtoInterface;

class FeePlanDto implements DtoInterface {
	private int $installmentsCount;
	private int $minAmount;
	private int $maxAmount;
	private int $deferredDays;
	private int $deferredMonths;

	public function __construct(
		int $installmentsCount = null,
		int $minAmount = null,
		int $maxAmount = null,
		int $deferredDays = null,
		int $deferredMonths = null
	) {
		$this->installmentsCount = $installmentsCount;
		$this->minAmount         = $minAmount;
		$this->maxAmount         = $maxAmount;
		$this->deferredDays      = $deferredDays;
		$this->deferredMonths    = $deferredMonths;
	}

	public function toArray(): array {
		return array(
			'installmentsCount' => $this->installmentsCount,
			'minAmount'         => $this->minAmount,
			'maxAmount'         => $this->maxAmount,
			'deferredDays'      => $this->deferredDays,
			'deferredMonths'    => $this->deferredMonths,
		);
	}
}
