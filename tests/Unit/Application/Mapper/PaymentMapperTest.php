<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\Client\Application\DTO\PaymentDto;
use Alma\Gateway\Application\Mapper\PaymentMapper;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Tests\Unit\Mocks\OrderAdapterMockFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class PaymentMapperTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
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

		$orderAdapterMock   = OrderAdapterMockFactory::createMock( $this );
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
				'billing_address'    => OrderAdapterMockFactory::resultArray()['addresses'][0],
				'shipping_address'   => OrderAdapterMockFactory::resultArray()['addresses'][1],
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
