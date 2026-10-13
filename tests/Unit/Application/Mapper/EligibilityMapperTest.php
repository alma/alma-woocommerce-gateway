<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\Client\Application\DTO\EligibilityDto;
use Alma\Gateway\Application\Mapper\EligibilityMapper;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\CustomerAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EligibilityMapperTest extends TestCase {

	/**
	 * A misconfigured fee plan (installments_count = 0) must not abort the
	 * whole eligibility call: the valid plans stay in the query (#617).
	 */
	public function testInvalidFeePlanIsSkippedWithoutBreakingTheQuery() {
		$mapper = new EligibilityMapper();

		$eligibilityDto = $mapper->buildEligibilityDto(
			$this->getCartAdapterMock(),
			$this->getCustomerAdapterMock(),
			new FeePlanListAdapter( array( $this->getFeePlanAdapterMock( 3 ), $this->getFeePlanAdapterMock( 0 ) ) )
		);

		$this->assertInstanceOf( EligibilityDto::class, $eligibilityDto );
		$this->assertCount( 1, $eligibilityDto->toArray()['queries'] );
		$this->assertSame( 3, $eligibilityDto->toArray()['queries'][0]['installments_count'] );
	}

	public function testSkippedFeePlanIsReportedToTheLoggerWithItsPlanKey() {
		$logger = Mockery::mock( LoggerInterface::class );
		$logger->shouldReceive( 'warning' )->once()->with(
			'Skipped an invalid fee plan when building the eligibility query.',
			Mockery::on( function ( $context ) {
				return isset( $context['error'], $context['plan_key'] )
					&& is_string( $context['error'] ) && '' !== $context['error']
					&& 'general_0_0_0' === $context['plan_key'];
			} )
		);

		$mapper = new EligibilityMapper( $logger );

		$eligibilityDto = $mapper->buildEligibilityDto(
			$this->getCartAdapterMock(),
			$this->getCustomerAdapterMock(),
			new FeePlanListAdapter( array( $this->getFeePlanAdapterMock( 3 ), $this->getFeePlanAdapterMock( 0 ) ) )
		);

		$this->assertCount( 1, $eligibilityDto->toArray()['queries'] );
	}

	/**
	 * When every fee plan is skipped, no query is left: the mapper reports
	 * null so the caller skips the API call — a query-less DTO means "no
	 * filter" and would return eligibility for every plan (#617).
	 */
	public function testReturnsNullWhenEveryFeePlanIsSkipped() {
		$logger = Mockery::mock( LoggerInterface::class );
		$logger->shouldReceive( 'warning' )->twice();

		$mapper = new EligibilityMapper( $logger );

		$eligibilityDto = $mapper->buildEligibilityDto(
			$this->getCartAdapterMock(),
			$this->getCustomerAdapterMock(),
			new FeePlanListAdapter( array( $this->getFeePlanAdapterMock( 0 ), $this->getFeePlanAdapterMock( 0 ) ) )
		);

		$this->assertNull( $eligibilityDto );
	}

	private function getFeePlanAdapterMock( $installmentsCount ) {
		$feePlanAdapter = $this->getMockBuilder( FeePlanAdapter::class )
			->disableOriginalConstructor()
			->getMock();
		$feePlanAdapter->method( 'getInstallmentsCount' )->willReturn( $installmentsCount );
		$feePlanAdapter->method( 'getDeferredDays' )->willReturn( 0 );
		$feePlanAdapter->method( 'getDeferredMonths' )->willReturn( 0 );
		$feePlanAdapter->method( 'getPlanKey' )->willReturn( 'general_' . $installmentsCount . '_0_0' );

		return $feePlanAdapter;
	}

	private function getCartAdapterMock() {
		$cartAdapter = $this->getMockBuilder( CartAdapter::class )
			->disableOriginalConstructor()
			->getMock();
		$cartAdapter->method( 'getCartTotal' )->willReturn( 10000 );

		return $cartAdapter;
	}

	private function getCustomerAdapterMock() {
		$customerAdapter = $this->getMockBuilder( CustomerAdapter::class )
			->disableOriginalConstructor()
			->getMock();
		$customerAdapter->method( 'getCustomerBillingAddress' )->willReturn( null );
		$customerAdapter->method( 'getCustomerShippingAddress' )->willReturn( null );

		return $customerAdapter;
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

}
