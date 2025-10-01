<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\API\Domain\Entity\FeePlan;
use Alma\Gateway\Application\Mapper\PaymentMapper;
use Alma\Gateway\Tests\Unit\Mocks\OrderAdapterMockFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class PaymentMapperTest extends TestCase {

	protected function setUp(): void {
	}

	protected function tearDown(): void {
		Mockery::close();
	}

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

		$orderAdapterMock = OrderAdapterMockFactory::createMock( $this );
		$feePlanMock      = $this->createMock( FeePlan::class );
		$feePlanMock->method( 'getInstallmentsCount' )->willReturn( 3 );
		$feePlanMock->method( 'getDeferredMonths' )->willReturn( 2 );
		$feePlanMock->method( 'getDeferredDays' )->willReturn( 0 );

		$paymentDto = $paymentMapper->buildPaymentDto( 'test_origin', $orderAdapterMock, $feePlanMock );
		$this->assertEquals(
			[
				'installments_count' => 3,
				'deferred_months'    => 2,
				'deferred_days'      => 0,
				'locale'             => 'fr_FR',
				'purchase_amount'    => 12300,
				'ipn_callback_url'   => 'https://example.com/ipn-webhook-url',
				'origin'             => 'test_origin',
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
}