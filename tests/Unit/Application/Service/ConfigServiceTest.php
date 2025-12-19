<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Repository\ConfigRepository;
use PHPUnit\Framework\TestCase;

class ConfigServiceTest extends TestCase {
	/**
	 * @var EncryptorHelper
	 */
	private $encryptionService;
	/**
	 * @var ConfigRepository
	 */
	private $configRepository;
	private ConfigService $configService;

	public function setUp(): void {
		$this->encryptionService = $this->createMock(EncryptorHelper::class);
		$this->configRepository = $this->createMock(ConfigRepository::class);
		$this->configService = new ConfigService(
			$this->encryptionService,
			$this->configRepository
		);
	}

	/**
	 * @dataProvider getSettingsProvider
	 * Test getGatewaysActive method with multiple active gateways
	 */
	public function testGetGatewaysActive($getSettings, $expected): void {
		$this->configRepository->expects($this->once())->method('getSettings')->willReturn($getSettings);
		$this->assertEquals($expected, $this->configService->getGatewaysActive());
	}

	/**
	 * Provides settings for testing
	 *
	 * @return array
	 */
	public static function getSettingsProvider(): array {
		return [
			'return pnx with p3x' => [
				'getSettings' => [
					'general_6_0_0_enabled' => false,
					'general_10_0_0_enabled' => false,
					'general_12_0_0_enabled' => false,
					'general_2_0_0_enabled' => false,
					'general_3_0_0_enabled' => true,
					'general_4_0_0_enabled' => false,
					'general_1_15_0_enabled' => false,
					'general_1_30_0_enabled' => false,
					'general_1_45_0_enabled' => false,
					'general_1_0_0_enabled' => false,
				],
				'expected' => ['pnx'],
			],
			'return pnx with p4x' => [
				'getSettings' => [
					'general_6_0_0_enabled' => false,
					'general_10_0_0_enabled' => false,
					'general_12_0_0_enabled' => false,
					'general_2_0_0_enabled' => false,
					'general_3_0_0_enabled' => false,
					'general_4_0_0_enabled' => true,
					'general_1_15_0_enabled' => false,
					'general_1_30_0_enabled' => false,
					'general_1_45_0_enabled' => false,
					'general_1_0_0_enabled' => false,
				],
				'expected' => ['pnx'],
			],
			'return credit with p10x' => [
				'getSettings' => [
					'general_6_0_0_enabled' => false,
					'general_10_0_0_enabled' => true,
					'general_12_0_0_enabled' => false,
					'general_2_0_0_enabled' => false,
					'general_3_0_0_enabled' => false,
					'general_4_0_0_enabled' => false,
					'general_1_15_0_enabled' => false,
					'general_1_30_0_enabled' => false,
					'general_1_45_0_enabled' => false,
					'general_1_0_0_enabled' => false,
				],
				'expected' => ['credit'],
			],
			'return paynow and paylater with P1x and +15' => [
				'getSettings' => [
					'general_6_0_0_enabled' => false,
					'general_10_0_0_enabled' => false,
					'general_12_0_0_enabled' => false,
					'general_2_0_0_enabled' => false,
					'general_3_0_0_enabled' => false,
					'general_4_0_0_enabled' => false,
					'general_1_15_0_enabled' => true,
					'general_1_30_0_enabled' => false,
					'general_1_45_0_enabled' => false,
					'general_1_0_0_enabled' => true,
				],
				'expected' => ['paylater', 'paynow']
			],
			'return all with P12x, P2x, +30 and P1x' => [
				'getSettings' => [
					'general_6_0_0_enabled' => false,
					'general_10_0_0_enabled' => false,
					'general_12_0_0_enabled' => true,
					'general_2_0_0_enabled' => true,
					'general_3_0_0_enabled' => false,
					'general_4_0_0_enabled' => false,
					'general_1_15_0_enabled' => false,
					'general_1_30_0_enabled' => true,
					'general_1_45_0_enabled' => false,
					'general_1_0_0_enabled' => true,
				],
				'expected' => ['credit', 'pnx', 'paylater', 'paynow']
			],
			'return nothing' => [
				'getSettings' => [
					'general_6_0_0_enabled' => false,
					'general_10_0_0_enabled' => false,
					'general_12_0_0_enabled' => false,
					'general_2_0_0_enabled' => false,
					'general_3_0_0_enabled' => false,
					'general_4_0_0_enabled' => false,
					'general_1_15_0_enabled' => false,
					'general_1_30_0_enabled' => false,
					'general_1_45_0_enabled' => false,
					'general_1_0_0_enabled' => false,
				],
				'expected' => []
			]
		];
	}
}