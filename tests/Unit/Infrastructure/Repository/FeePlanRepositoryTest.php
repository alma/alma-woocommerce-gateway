<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Infrastructure\Repository;

use Alma\Gateway\Application\Provider\EligibilityProviderFactory;
use Alma\Gateway\Application\Provider\FeePlanProviderFactory;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use PHPUnit\Framework\TestCase;

class FeePlanRepositoryTest extends TestCase
{
	/*** @var ConfigService */
	private $configService;

	public function setUp(): void {
		$this->configService = $this->createMock( ConfigService::class);
		$feePlanProviderFactory = $this->createMock( FeePlanProviderFactory::class);
		$eligibilityProviderFactory = $this->createMock( EligibilityProviderFactory::class);
		$this->feePlanRepository = new FeePlanRepository(
			$this->configService,
			$feePlanProviderFactory,
			$eligibilityProviderFactory
		);
	}

	public function testRetrieveFeePlans(): void {
		//$this->assertNull($this->feePlanRepository->retrieveFeePlans());
	}
}