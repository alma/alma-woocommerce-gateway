<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway\Frontend;

use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Infrastructure\Helper\FormHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Tests\Unit\Fixtures\FeePlanFixturesFactory;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class PnxGatewayTest extends TestCase {

	private PnxGateway $pnxGateway;
	private FeePlanListAdapter $feePlanListAdapter;
	private FeePlanFixturesFactory $feePlanFixturesFactory;

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
		$this->feePlanFixturesFactory = new FeePlanFixturesFactory();

		$this->feePlanRepository = $this->createMock(FeePlanRepository::class);
		$this->formHelperMock = $this->createMock( FormHelper::class);
		$this->configService = $this->createMock(ConfigService::class);
		$this->cartAdapter = $this->createMock(CartAdapter::class);
		$this->excludedProductsHelper = $this->createMock( ExcludedProductsHelper::class);
		$this->pnxGateway = new PnxGateway();
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
		$this->feePlanRepository = null;
		$this->formHelperMock = null;
		$this->configService = null;
		$this->cartAdapter = null;
		$this->excludedProductsHelper = null;
	}

	/**
	 * TODO: enable tests once the refacto on the constructor is done
	 * @throws ParametersException
	 * @throws FeePlanRepositoryException
	 */
	public function wiptestIsAvailableWithoutPlanEnable() {
		$feePlanAdapter1 = $this->feePlanFixturesFactory->getP2x(false);
		$feePlanAdapter2 = $this->feePlanFixturesFactory->getP3x(false);

		$feePlanListAdapter = new FeePlanListAdapter([$feePlanAdapter1, $feePlanAdapter2]);
		$this->feePlanRepository->method('getAll')
		                        ->willReturn($feePlanListAdapter);

		$this->assertFalse($this->pnxGateway->is_available());
	}

	/**
	 * TODO: enable tests once the refacto on the constructor is done
	 * @throws FeePlanRepositoryException
	 * @throws ParametersException
	 */
	public function wiptestIsAvailableWithPlanEnableAndNoExcludedProduct() {
		$feePlanAdapter1 = $this->feePlanFixturesFactory->getP2x(true);
		$feePlanAdapter2 = $this->feePlanFixturesFactory->getP3x(false);

		$feePlanListAdapter = new FeePlanListAdapter([$feePlanAdapter1, $feePlanAdapter2]);
		$this->feePlanRepository->method('getAll')
		                        ->willReturn($feePlanListAdapter);
		$this->excludedProductsHelper->method('canDisplayOnCheckoutPage')->willReturn(true);

		$this->assertTrue($this->pnxGateway->is_available());
	}

	/**
	 * TODO: enable tests once the refacto on the constructor is done
	 * @throws FeePlanRepositoryException
	 * @throws ParametersException
	 */
	public function wiptestIsAvailableWithPlanEnableAndProductInCartExclude() {
		$feePlanAdapter1 = $this->feePlanFixturesFactory->getP2x(true);
		$feePlanAdapter2 = $this->feePlanFixturesFactory->getP3x(false);

		$feePlanListAdapter = new FeePlanListAdapter([$feePlanAdapter1, $feePlanAdapter2]);
		$this->feePlanRepository->method('getAll')
		                        ->willReturn($feePlanListAdapter);
		$this->excludedProductsHelper->method('canDisplayOnCheckoutPage')->willReturn(false);

		$this->assertFalse($this->pnxGateway->is_available());
	}
}
