<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Client\Application\DTO\MerchantBusinessEvent\CartInitiatedBusinessEventDto;
use Alma\Client\Application\DTO\MerchantBusinessEvent\OrderConfirmedBusinessEventDto;
use Alma\Client\Application\Exception\ParametersException;
use Alma\Client\Domain\Entity\Eligibility;
use Alma\Client\Domain\Entity\EligibilityList;
use Alma\Gateway\Application\Exception\Provider\MerchantProviderException;
use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Provider\MerchantProvider;
use Alma\Gateway\Application\Provider\MerchantProviderFactory;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use Automattic\WooCommerce\Admin\Notes\StartDropshippingBusiness;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use stdClass;

class BusinessEventsServiceTest extends TestCase {
	public function setUp(): void {
		Monkey\setUp();

		// Mock de la classe OrderStatus de WooCommerce
		if ( ! class_exists( '\Automattic\WooCommerce\Enums\OrderStatus' ) ) {
			eval( '
            namespace Automattic\WooCommerce\Enums;
            class OrderStatus {
                const PENDING = "pending";
                const PROCESSING = "processing";
                const ON_HOLD = "on-hold";
                const COMPLETED = "completed";
                const CANCELLED = "cancelled";
                const REFUNDED = "refunded";
                const FAILED = "failed";
            }
        ' );
		}

		$this->sessionHelper            = $this->createMock( SessionHelper::class );
		$this->businessEventsRepository = $this->createMock( BusinessEventsRepository::class );
		$this->merchantProvider         = $this->createMock( MerchantProvider::class );
		$this->merchantProviderFactory  = $this->createMock( MerchantProviderFactory::class );

		$this->merchantProviderFactory->method( '__invoke' )
		                              ->willReturn( $this->merchantProvider );

		$this->businessEventsService = new BusinessEventsService(
			$this->sessionHelper,
			$this->businessEventsRepository,
			$this->merchantProviderFactory
		);
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
		$this->sessionHelper            = null;
		$this->businessEventsRepository = null;
		$this->merchantProvider         = null;
		$this->merchantProviderFactory  = null;
		$this->businessEventsService    = null;
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnCartInitiatedWithoutCartExistAndCartNotConverted(): void {
		$cartId  = 12345;
		$cartRow = null;

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'getSession' )
		                    ->with( 'alma_cart_id' )
		                    ->willReturn( $cartId );

		$this->businessEventsRepository->method( 'getCartRowIfExist' )
		                               ->with( $cartId )
		                               ->willReturn( $cartRow );

		$this->businessEventsRepository->expects( $this->once() )
		                               ->method( 'saveCartId' )
		                               ->with( $cartId );

		$this->merchantProvider->expects( $this->once() )
		                       ->method( 'sendCartInitiatedBusinessEvent' )
		                       ->with( $this->isInstanceOf( CartInitiatedBusinessEventDto::class ) );

		$this->businessEventsService->onCartInitiated();
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnCartInitiatedWithCartExistAndCartNotConverted(): void {
		$cartId            = 12345;
		$cartRow           = new Stdclass();
		$cartRow->cart_id  = $cartId;
		$cartRow->order_id = null;

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'getSession' )
		                    ->with( 'alma_cart_id' )
		                    ->willReturn( $cartId );

		$this->businessEventsRepository->method( 'getCartRowIfExist' )
		                               ->with( $cartId )
		                               ->willReturn( $cartRow );

		$this->businessEventsRepository->expects( $this->never() )
		                               ->method( 'saveCartId' )
		                               ->with( $cartId );

		$this->merchantProvider->expects( $this->never() )
		                       ->method( 'sendCartInitiatedBusinessEvent' )
		                       ->with( $this->isInstanceOf( CartInitiatedBusinessEventDto::class ) );

		$this->businessEventsService->onCartInitiated();
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnCartInitiatedWithCartExistAndCartConverted(): void {
		$oldCartId = 12345;

		$cartRow           = new Stdclass();
		$cartRow->cart_id  = $oldCartId;
		$cartRow->order_id = 42;

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'getSession' )
		                    ->with( 'alma_cart_id' )
		                    ->willReturn( $oldCartId );

		$this->businessEventsRepository->expects( $this->exactly( 2 ) )
		                               ->method( 'getCartRowIfExist' )
		                               ->willReturnOnConsecutiveCalls( $cartRow, null );

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'unsetKeySession' )
		                    ->with( 'alma_cart_id' );

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'setSession' )
		                    ->with( 'alma_cart_id' );

		$this->businessEventsRepository->expects( $this->once() )
		                               ->method( 'saveCartId' );

		$this->merchantProvider->expects( $this->once() )
		                       ->method( 'sendCartInitiatedBusinessEvent' )
		                       ->with( $this->isInstanceOf( CartInitiatedBusinessEventDto::class ) );

		$this->businessEventsService->onCartInitiated();
	}

	/**
	 * @dataProvider eligibleListProvider
	 */
	public function testUpdateEligibilityWithEligibleList( $eligibleList, $isEligible ): void {
		$cartId = 12345;

		$this->businessEventsService = $this->getMockBuilder( BusinessEventsService::class )
		                                    ->setConstructorArgs( [
			                                    $this->sessionHelper,
			                                    $this->businessEventsRepository,
			                                    $this->merchantProviderFactory
		                                    ] )
		                                    ->onlyMethods( [ 'sessionCartId' ] )
		                                    ->getMock();

		$this->businessEventsService->expects( $this->once() )
		                            ->method( 'sessionCartId' )
		                            ->willReturn( $cartId );

		$this->businessEventsRepository->expects( $this->once() )
		                               ->method( 'saveEligibility' )
		                               ->with( $cartId, $isEligible );

		$this->businessEventsService->updateEligibility( $eligibleList );
	}

	/**
	 * @throws BusinessEventsServiceException
	 */
	public function testOnOrderConfirmedOldStatusNotPendingMakeNothing(): void {
		$orderMock = $this->createMock( OrderAdapter::class );
		$orderMock->method( 'getId' )->willReturn( 42 );

		$this->merchantProvider->expects( $this->never() )->method( 'sendCartInitiatedBusinessEvent' );
		$this->businessEventsService->onOrderConfirmed( 'not_pending', 'processing', $orderMock );
	}

	/**
	 * Not Paid says the order status is not in 'processing' or 'completed' for WC,
	 * with the function wc_get_is_paid_statuses()
	 * @throws BusinessEventsServiceException
	 */
	public function testOnOrderConfirmedNewStatusNotPaidOrOnHoldMakeNothing(): void {
		$orderMock = $this->createMock( OrderAdapter::class );
		$orderMock->method( 'getId' )->willReturn( 42 );

		Functions\when( 'wc_get_is_paid_statuses' )
			->justReturn( [ 'processing', 'completed' ] );

		$this->merchantProvider->expects( $this->never() )->method( 'sendCartInitiatedBusinessEvent' );
		$this->businessEventsService->onOrderConfirmed( 'pending', 'not_paid', $orderMock );
	}

	/**
	 * @throws BusinessEventsServiceException
	 * @throws ParametersException
	 */
	public function testOnOrderConfirmedOldStatusPendingNewStatusPaidPaymentMethodNotAlma(): void {
		$this->businessEventsRepository->method( 'getRowByOrderId' )
		                               ->with( 42 )
		                               ->willReturn( (object) [
			                               'cart_id'          => 4242,
			                               'alma_payment_id'  => '',
			                               'is_bnpl_eligible' => 1,
		                               ] );

		$orderMock = $this->createMock( OrderAdapter::class );
		$orderMock->method( 'getId' )->willReturn( 42 );
		$orderMock->method( 'getPaymentMethod' )->willReturn( 'paypal' );

		Functions\when( 'wc_get_is_paid_statuses' )
			->justReturn( [ 'processing', 'completed' ] );

		$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
			false,
			false,
			true,
			'42',
			4242,
			''
		);

		$this->merchantProvider->expects( $this->once() )
		                       ->method( 'sendOrderConfirmedBusinessEvent' )
		                       ->with( $orderConfirmedBusinessEvent );
		$this->businessEventsService->onOrderConfirmed( 'pending', 'processing', $orderMock );
	}

	/**
	 * @throws BusinessEventsServiceException
	 * @throws ParametersException
	 */
	public function testOnOrderConfirmedOldStatusPendingNewStatusPaidPaymentMethodAlma(): void {
		$this->businessEventsRepository->method( 'getRowByOrderId' )
		                               ->with( 42 )
		                               ->willReturn( (object) [
			                               'cart_id'          => 4242,
			                               'alma_payment_id'  => 'payment_alma_id',
			                               'is_bnpl_eligible' => 1,
		                               ] );

		$orderMock = $this->createMock( OrderAdapter::class );
		$orderMock->method( 'getId' )->willReturn( 42 );
		$orderMock->method( 'getPaymentMethod' )->willReturn( 'alma_pnx_gateway' );

		Functions\when( 'wc_get_is_paid_statuses' )
			->justReturn( [ 'processing', 'completed' ] );

		$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
			false,
			true,
			true,
			'42',
			4242,
			'payment_alma_id'
		);

		$this->merchantProvider->expects( $this->once() )
		                       ->method( 'sendOrderConfirmedBusinessEvent' )
		                       ->with( $orderConfirmedBusinessEvent );
		$this->businessEventsService->onOrderConfirmed( 'pending', 'processing', $orderMock );
	}

	/**
	 * @throws ParametersException
	 */
	public function testOnOrderConfirmedCatchMerchantServiceException(): void {
		$this->businessEventsRepository->method( 'getRowByOrderId' )
		                               ->with( 42 )
		                               ->willReturn( (object) [
			                               'cart_id'          => 4242,
			                               'alma_payment_id'  => 'payment_alma_id',
			                               'is_bnpl_eligible' => 1,
		                               ] );

		$orderMock = $this->createMock( OrderAdapter::class );
		$orderMock->method( 'getId' )->willReturn( 42 );
		$orderMock->method( 'getPaymentMethod' )->willReturn( 'alma_pnx_gateway' );

		Functions\when( 'wc_get_is_paid_statuses' )
			->justReturn( [ 'processing', 'completed' ] );

		$orderConfirmedBusinessEvent = new OrderConfirmedBusinessEventDto(
			false,
			true,
			true,
			'42',
			4242,
			'payment_alma_id'
		);

		$this->merchantProvider->expects( $this->once() )
		                       ->method( 'sendOrderConfirmedBusinessEvent' )
		                       ->with( $orderConfirmedBusinessEvent )
		                       ->willThrowException( new MerchantProviderException() );
		$this->expectException( BusinessEventsServiceException::class );
		$this->businessEventsService->onOrderConfirmed( 'pending', 'processing', $orderMock );
	}

	public function testOnCreateOrder(): void {
		$cartId = 12345;

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'getSession' )
		                    ->with( 'alma_cart_id' )
		                    ->willReturn( $cartId );

		$this->businessEventsRepository->expects( $this->once() )
		                               ->method( 'saveOrderId' )
		                               ->with( $cartId, 42 );
		$this->businessEventsService->onCreateOrder( 42 );
	}

	public function testSaveAlmaPaymentId(): void {
		$cartId = 12345;

		$this->sessionHelper->expects( $this->once() )
		                    ->method( 'getSession' )
		                    ->with( 'alma_cart_id' )
		                    ->willReturn( $cartId );

		$this->businessEventsRepository->expects( $this->once() )
		                               ->method( 'saveAlmaPaymentId' )
		                               ->with( $cartId, 'payment_alma_id' );
		$this->businessEventsService->saveAlmaPaymentId( 'payment_alma_id' );
	}

	/**
	 * @throws ParametersException
	 */
	public function eligibleListProvider(): array {
		return [
			'Eligible List'     => [
				new EligibilityList( [
					new Eligibility( [
						'eligible'                   => true,
						'deferred_days'              => 0,
						'deferred_months'            => 0,
						'installments_count'         => 3,
						'customer_fee'               => 0,
						'customer_total_cost_amount' => 0,
						'customer_total_cost_bps'    => 0,
						'payment_plan'               => [],
						'annual_interest_rate'       => 0,
					] )
				] ),
				true
			],
			'Non-Eligible List' => [
				new EligibilityList( [
					new Eligibility( [
						'eligible'           => false,
						'deferred_days'      => 0,
						'deferred_months'    => 0,
						'installments_count' => 3,
						'constraints'        => [],
						'reasons'            => [],
					] )
				] ),
				false
			],
		];
	}
}