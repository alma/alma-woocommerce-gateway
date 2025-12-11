<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Infrastructure\Repository;

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use PHPUnit\Framework\TestCase;

class FeePlanRepositoryTest extends TestCase
{
	/*** @var ConfigService */
	private $configService;

	public function setUp(): void {
		$this->configService = $this->createMock( ConfigService::class);
		$this->feePlanProvider = $this->createMock( FeePlanRepository::class);
		$this->feePlanRepository = new FeePlanRepository($this->configService);
		$this->feePlanRepository->setFeePlanProvider($this->feePlanProvider);
	}

	public function testRetrieveFeePlans(): void {
		$this->assertNull($this->feePlanRepository->retrieveFeePlans());
	}
}