<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\API\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use PHPUnit\Framework\TestCase;

class BusinessEventsServiceTest extends TestCase
{
	public function setUp(): void {
		$this->sessionHelper = $this->createMock(SessionHelper::class);
		$this->businessEventsRepository = $this->createMock(BusinessEventsRepository::class);
		$this->merchantEndpoint = $this->createMock( MerchantEndpoint::class);
		$this->busisnessEventsService = new BusinessEventsService(
			$this->sessionHelper,
			$this->businessEventsRepository,
			$this->merchantEndpoint
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

		$this->sessionHelper->expects($this->once())
			->method('setSession')
			->with(BusinessEventsService::ALMA_CART_ID, $cartId);

		$this->merchantEndpoint->expects($this->once())
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

		$this->sessionHelper->expects($this->never())
            ->method('setSession')
            ->with(BusinessEventsService::ALMA_CART_ID, $cartId);

		$this->merchantEndpoint->expects($this->never())
           ->method('sendCartInitiatedBusinessEvent')
           ->with($this->isInstanceOf( CartInitiatedBusinessEventDto::class));

		$this->busisnessEventsService->onCartInitiated();
	}
}