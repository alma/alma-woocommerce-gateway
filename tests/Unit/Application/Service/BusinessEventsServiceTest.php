<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Provider\MerchantProvider;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use PHPUnit\Framework\TestCase;

class BusinessEventsServiceTest extends TestCase
{
	public function setUp(): void {
		$this->sessionHelper = $this->createMock(SessionHelper::class);
		$this->businessEventsRepository = $this->createMock(BusinessEventsRepository::class);
		$this->merchantProvider = $this->createMock( MerchantProvider::class);
		$this->busisnessEventsService = new BusinessEventsService(
			$this->sessionHelper,
			$this->businessEventsRepository,
			$this->merchantProvider
		);
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnCartInitiatedWithoutCartIdSaved(): void {
		$cartId = 12345;

		$this->sessionHelper->expects($this->once())
			->method('getSession')
			->with('alma_cart_id')
			->willReturn($cartId);

		$this->businessEventsRepository->expects($this->once())
			->method( 'alreadyExist' )
			->with($cartId)
			->willReturn(false);

		$this->businessEventsRepository->expects($this->once())
			->method('saveCartId')
			->with($cartId);

		$this->merchantProvider->expects($this->once())
			->method('sendCartInitiatedBusinessEvent')
			->with($this->isInstanceOf( CartInitiatedBusinessEventDto::class));

		$this->busisnessEventsService->onCartInitiated();
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnCartInitiatedWithCartIdSaved(): void {
		$cartId = 12345;

		$this->sessionHelper->expects($this->once())
            ->method('getSession')
            ->with('alma_cart_id')
            ->willReturn($cartId);

		$this->businessEventsRepository->expects($this->once())
           ->method( 'alreadyExist' )
           ->with($cartId)
           ->willReturn(true);

		$this->businessEventsRepository->expects($this->never())
           ->method('saveCartId')
           ->with($cartId);

		$this->merchantProvider->expects($this->never())
           ->method('sendCartInitiatedBusinessEvent')
           ->with($this->isInstanceOf( CartInitiatedBusinessEventDto::class));

		$this->busisnessEventsService->onCartInitiated();
	}

	/**
	 * @dataProvider eligibleListProvider
	 */
	public function testUpdateEligibilityWithEligibleList($eligibleList, $isEligible): void {
		$cartId = 12345;

		$this->busisnessEventsService = $this->getMockBuilder(BusinessEventsService::class)
             ->setConstructorArgs([
                 $this->sessionHelper,
                 $this->businessEventsRepository,
                 $this->merchantProvider
             ])
             ->onlyMethods(['getCartId'])
             ->getMock();

		$this->busisnessEventsService->expects($this->once())
             ->method('getCartId')
             ->willReturn($cartId);

		$this->businessEventsRepository->expects($this->once())
			->method('saveEligibility')
			->with($cartId, $isEligible);

		$this->busisnessEventsService->updateEligibility($eligibleList);
	}

	public function eligibleListProvider(): array {
		return [
			'Eligible List' => [
				new EligibilityList([
					new Eligibility([
						'eligible' => true,
						'deferred_days' => 0,
						'deferred_months' => 0,
						'installments_count' => 3,
						'customer_fee' => 0,
						'customer_total_cost_amount' => 0,
						'customer_total_cost_bps' => 0,
						'payment_plan' => [],
						'annual_interest_rate' => 0,
					])
				]),
				true
			],
			'Non-Eligible List' => [
				new EligibilityList([
					new Eligibility([
						'eligible' => false,
						'deferred_days' => 0,
						'deferred_months' => 0,
						'installments_count' => 3,
						'constraints' => [],
						'reasons' => [],
					])
				]),
				false
			],
		];
	}
}