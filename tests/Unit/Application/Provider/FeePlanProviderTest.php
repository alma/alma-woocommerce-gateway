<?php

namespace Alma\Gateway\Tests\Unit\Application\Provider;

use Alma\API\Domain\Entity\FeePlanList;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\MerchantEndpointException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Provider\FeePlanProvider;
use PHPUnit\Framework\TestCase;

class FeePlanProviderTest extends TestCase {

	private $feePlanProvider;
	private $merchantEndpoint;


	public function testGetFeePlanListCallsMerchantEndpointOnlyOnce() {
		$feePlanList = $this->createMock( FeePlanList::class );
		$feePlanList->expects( $this->once() )->method( 'filterFeePlanList' )->willReturn( $feePlanList );
		$this->merchantEndpoint->expects( $this->once() )
		                       ->method( 'getFeePlanList' )
		                       ->willReturn( $feePlanList );

		$createdFeePlanList = $this->feePlanProvider->getFeePlanList();
		$this->assertSame( $feePlanList, $createdFeePlanList );
		$createdFeePlanList2 = $this->feePlanProvider->getFeePlanList();
		$this->assertSame( $feePlanList, $createdFeePlanList2 );
	}

	public function testGetFeePlanListCallsMerchantEndpointTwiceWithForceRefresh() {
		$feePlanList = $this->createMock( FeePlanList::class );
		$feePlanList->expects( $this->exactly( 2 ) )->method( 'filterFeePlanList' )->willReturn( $feePlanList );
		$this->merchantEndpoint->expects( $this->exactly( 2 ) )
		                       ->method( 'getFeePlanList' )
		                       ->willReturn( $feePlanList );

		$createdFeePlanList = $this->feePlanProvider->getFeePlanList();
		$this->assertSame( $feePlanList, $createdFeePlanList );
		$createdFeePlanList2 = $this->feePlanProvider->getFeePlanList( true );
		$this->assertSame( $feePlanList, $createdFeePlanList2 );
	}

	public function testGetFeePlanListCallsMerchantEndpointThrowException() {
		$this->expectException( FeePlanServiceException::class );
		$this->expectExceptionMessage( 'Error retrieving fee plans: API error' );

		$this->merchantEndpoint->expects( $this->once() )
		                       ->method( 'getFeePlanList' )
		                       ->willThrowException( new MerchantEndpointException( 'API error' ) );

		$this->feePlanProvider->getFeePlanList();
	}

	protected function setUp(): void {
		$this->merchantEndpoint = $this->createMock( MerchantEndpoint::class );
		$this->feePlanProvider  = new FeePlanProvider( $this->merchantEndpoint );
	}

	protected function tearDown(): void {
		$this->merchantEndpoint = null;
		$this->feePlanProvider  = null;
	}

}
