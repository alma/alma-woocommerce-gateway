<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;
use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\MerchantServiceException;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Provider\MerchantProvider;
use Alma\Gateway\Application\Provider\MerchantProviderFactory;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Helper\OrderHelper;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use Automattic\WooCommerce\Admin\Overrides\Order;
use Mockery;
use PHPUnit\Framework\TestCase;

class BusinessEventsServiceTest extends TestCase
{
	public function setUp(): void {
		$this->sessionHelper = $this->createMock(SessionHelper::class);
		$this->businessEventsRepository = $this->createMock(BusinessEventsRepository::class);
		$this->merchantProvider = $this->createMock( MerchantProvider::class);
		$this->merchantProviderFactory = $this->createMock( MerchantProviderFactory::class);

		$this->merchantProviderFactory->method('__invoke')
              ->willReturn($this->merchantProvider);

		$this->businessEventsService = new BusinessEventsService(
			$this->sessionHelper,
			$this->businessEventsRepository,
			$this->merchantProviderFactory
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

		$this->businessEventsService->onCartInitiated();
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

		$this->businessEventsService->onCartInitiated();
	}

	/**
	 * @dataProvider eligibleListProvider
	 */
	public function testUpdateEligibilityWithEligibleList($eligibleList, $isEligible): void {
		$cartId = 12345;

		$this->businessEventsService = $this->getMockBuilder(BusinessEventsService::class)
             ->setConstructorArgs([
                 $this->sessionHelper,
                 $this->businessEventsRepository,
                 $this->merchantProviderFactory
             ])
             ->onlyMethods(['getCartId'])
             ->getMock();

		$this->businessEventsService->expects($this->once())
             ->method('getCartId')
             ->willReturn($cartId);

		$this->businessEventsRepository->expects($this->once())
			->method('saveEligibility')
			->with($cartId, $isEligible);

		$this->businessEventsService->updateEligibility($eligibleList);
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnOrderConfirmedOldStatusNotPendingMakeNothing(): void {
		$orderMock = $this->createMock(OrderAdapter::class);
		$orderMock->method('getId')->willReturn(42);

		$this->merchantProvider->expects($this->never())->method('sendCartInitiatedBusinessEvent');
		$this->businessEventsService->onOrderConfirmed('not_pending', 'processing', $orderMock);
	}

	/**
	 * Not Paid says the order status is not in 'processing' or 'completed' for WC,
	 * with the function wc_get_is_paid_statuses()
	 * @throws BusinessEventsServiceException
	 */
	public function testOnOrderConfirmedNewStatusNotPaidOrOnHoldMakeNothing(): void {
		$orderMock = $this->createMock(OrderAdapter::class);
		$orderMock->method('getId')->willReturn(42);

		$orderHelperMock = Mockery::mock('alias:' . OrderHelper::class);
		$orderHelperMock->shouldReceive('wcGetIsPaidStatuses')
		                ->andReturn(['processing', 'completed']);

		$this->merchantProvider->expects($this->never())->method('sendCartInitiatedBusinessEvent');
		$this->businessEventsService->onOrderConfirmed('pending', 'not_paid', $orderMock);
	}

	/**
	 * @throws BusinessEventsServiceException
	 * @throws ParametersException
	 */
	public function testOnOrderConfirmedOldStatusPendingNewStatusPaidPaymentMethodNotAlma(): void {
		$this->businessEventsRepository->method('getRowByOrderId')
			->with(42)
			 ->willReturn((object)[
				 'cart_id' => 4242,
				 'alma_payment_id' => '',
				 'is_bnpl_eligible' => 1,
			 ]);

		$orderMock = $this->createMock(OrderAdapter::class);
		$orderMock->method('getId')->willReturn(42);
		$orderMock->method('getPaymentMethod')->willReturn('paypal');

		$orderHelperMock = Mockery::mock('alias:' . OrderHelper::class);
		$orderHelperMock->shouldReceive('wcGetIsPaidStatuses')
		                ->andReturn(['processing', 'completed']);

		$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
			false,
			false,
			true,
			'42',
			4242,
			''
		);

		$this->merchantProvider->expects($this->once())
               ->method('sendOrderConfirmedBusinessEvent')
               ->with($orderConfirmedBusinessEvent);
		$this->businessEventsService->onOrderConfirmed('pending', 'processing', $orderMock);
	}

	/**
	 * @throws BusinessEventsServiceException
	 * @throws ParametersException
	 */
	public function testOnOrderConfirmedOldStatusPendingNewStatusPaidPaymentMethodAlma(): void {
		$this->businessEventsRepository->method('getRowByOrderId')
               ->with(42)
               ->willReturn((object)[
                   'cart_id' => 4242,
                   'alma_payment_id' => 'payment_alma_id',
                   'is_bnpl_eligible' => 1,
               ]);

		$orderMock = $this->createMock(OrderAdapter::class);
		$orderMock->method('getId')->willReturn(42);
		$orderMock->method('getPaymentMethod')->willReturn('alma_pnx_gateway');

		$orderHelperMock = Mockery::mock('alias:' . OrderHelper::class);
		$orderHelperMock->shouldReceive('wcGetIsPaidStatuses')
            ->andReturn(['processing', 'completed']);

		$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
			false,
			true,
			true,
			'42',
			4242,
			'payment_alma_id'
		);

		$this->merchantProvider->expects($this->once())
               ->method('sendOrderConfirmedBusinessEvent')
               ->with($orderConfirmedBusinessEvent);
		$this->businessEventsService->onOrderConfirmed('pending', 'processing', $orderMock);
	}

	/**
	 * @throws ParametersException
	 */
	public function testOnOrderConfirmedCatchMerchantServiceException(): void {
		$this->businessEventsRepository->method('getRowByOrderId')
		                               ->with(42)
		                               ->willReturn((object)[
			                               'cart_id' => 4242,
			                               'alma_payment_id' => 'payment_alma_id',
			                               'is_bnpl_eligible' => 1,
		                               ]);

		$orderMock = $this->createMock(OrderAdapter::class);
		$orderMock->method('getId')->willReturn(42);
		$orderMock->method('getPaymentMethod')->willReturn('alma_pnx_gateway');

		$orderHelperMock = Mockery::mock('alias:' . OrderHelper::class);
		$orderHelperMock->shouldReceive('wcGetIsPaidStatuses')
		                ->andReturn(['processing', 'completed']);

		$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
			false,
			true,
			true,
			'42',
			4242,
			'payment_alma_id'
		);

		$this->merchantProvider->expects($this->once())
               ->method('sendOrderConfirmedBusinessEvent')
               ->with($orderConfirmedBusinessEvent)
				->willThrowException(new MerchantServiceException());
		$this->expectException(BusinessEventsServiceException::class);
		$this->businessEventsService->onOrderConfirmed('pending', 'processing', $orderMock);
	}

	public function testOnCreateOrder(): void {
		$cartId = 12345;

		$this->sessionHelper->expects($this->once())
                ->method('getSession')
                ->with('alma_cart_id')
                ->willReturn($cartId);

		$this->businessEventsRepository->expects($this->once())
            ->method('updateOrderId')
			->with($cartId, 42);
		$this->businessEventsService->onCreateOrder(42);
	}

	public function testSaveAlmaPaymentId(): void {
		$cartId = 12345;

		$this->sessionHelper->expects($this->once())
		                    ->method('getSession')
		                    ->with('alma_cart_id')
		                    ->willReturn($cartId);

		$this->businessEventsRepository->expects($this->once())
		                               ->method('saveAlmaPaymentId')
		                               ->with($cartId, 'payment_alma_id');
		$this->businessEventsService->saveAlmaPaymentId('payment_alma_id');
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