<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Entity\FeePlan;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Infrastructure\Helper\FormHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class PnxGatewayTest extends TestCase {
	private PnxGateway $pnxGateway;

	public function setUp(): void {
		Monkey\setUp();

		Functions\when('__')->justReturn('');
		Functions\expect('add_action')
			->once()
			->withArgs(function ($event, $callback, $priority) {
				$this->assertSame('woocommerce_update_options_payment_gateways_alma_config_gateway', $event);
				// Vérifier que le callback est bien un tableau [objet, méthode]
				$this->assertIsArray($callback);
				$this->assertInstanceOf(PnxGateway::class, $callback[0]);
				return true;
			});
		$feePlanAdapterMock1 = $this->createMock(FeePlanAdapter::class);
		$feePlanAdapterMock1->method('isAvailable')->willReturn(true);

		$feePlanAdapterMock2 = $this->createMock(FeePlanAdapter::class);
		$feePlanAdapterMock2->method('isAvailable')->willReturn(false);

		$this->feePlanListAdapterMock = $this->createMock( FeePlanListAdapter::class);
//		$this->feePlanListAdapterMock->addList( $feePlanAdapterMock1 );

		$this->feePlanListAdapterMock->method('filterFeePlanList')
		                             ->willReturnSelf();
		$this->feePlanListAdapterMock->method('filterEnabled')
		                             ->willReturnSelf();

		$this->feePlanRepository = $this->createMock(FeePlanRepository::class);
		$this->feePlanRepository->method('getAll')
		                        ->willReturn($this->feePlanListAdapterMock);

		$this->formHelperMock = $this->createMock( FormHelper::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->cartAdapter = $this->createMock(CartAdapter::class);
		$this->excludedProductsHelper = $this->createMock( ExcludedProductsHelper::class);
		$this->pnxGateway = new PnxGateway(
			$this->formHelperMock,
			$this->feePlanRepository,
			$this->configService,
			$this->cartAdapter,
			$this->excludedProductsHelper
		);
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testIsAvailable() {
//		$this->assertFalse($this->pnxGateway->is_available());
	}
}
