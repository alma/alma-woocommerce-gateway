<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\Client\Application\DTO\PaymentDto;
use Alma\Gateway\Application\Mapper\PaymentMapper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Tests\Unit\Mocks\OrderAdapterMockTrait;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class PaymentMapperTest extends TestCase {
	use OrderAdapterMockTrait;

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function testBuildPaymentDto(): void {
		$contextHelperMock = Mockery::mock( 'alias:Alma\Gateway\Infrastructure\Helper\ContextHelper' );
		$contextHelperMock->shouldReceive( 'getWebhookUrl' )
		                  ->twice()
		                  ->andReturnValues( [
			                  'https://example.com/ipn-webhook-url',
			                  'https://example.com/customer-webhook-url'
		                  ] );
		$contextHelperMock->shouldReceive( 'getLocale' )
		                  ->once()
		                  ->andReturn( 'fr_FR' );


		$paymentMapper = new PaymentMapper();

		$orderAdapterMock   = $this->createOrderAdapterMock();
		$feePlanAdapterMock = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapterMock->method( 'getInstallmentsCount' )->willReturn( 3 );
		$feePlanAdapterMock->method( 'getDeferredMonths' )->willReturn( 2 );
		$feePlanAdapterMock->method( 'getDeferredDays' )->willReturn( 0 );

		$paymentDto = $paymentMapper->buildPaymentDto(
			PaymentDto::ORIGIN_ONLINE,
			$orderAdapterMock,
			$feePlanAdapterMock
		);
		$this->assertEquals(
			[
				'installments_count' => 3,
				'deferred_months'    => 2,
				'deferred_days'      => 0,
				'locale'             => 'fr_FR',
				'purchase_amount'    => 12300,
				'ipn_callback_url'   => 'https://example.com/ipn-webhook-url',
				'origin'             => PaymentDto::ORIGIN_ONLINE,
				'return_url'         => 'https://example.com/customer-webhook-url',
				'billing_address'    => self::orderAdapterResultArray()['addresses'][0],
				'shipping_address'   => self::orderAdapterResultArray()['addresses'][1],
				'cart'               => [ 'items' => [] ],
				'custom_data'        => [
					'order_id'  => 123456,
					'order_key' => 'orderKey',
				],
			],
			$paymentDto->toArray()
		);
	}

	protected function setUp(): void {
	}

	protected function tearDown(): void {
		Mockery::close();
	}
}
