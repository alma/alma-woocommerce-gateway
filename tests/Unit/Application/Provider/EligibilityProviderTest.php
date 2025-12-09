<?php

namespace Alma\Gateway\Tests\Unit\Application\Provider;

use Alma\API\Application\DTO\EligibilityDto;
use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\API\Infrastructure\Endpoint\EligibilityEndpoint;
use Alma\API\Infrastructure\Exception\Endpoint\EligibilityEndpointException;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use PHPUnit\Framework\TestCase;

class EligibilityProviderTest extends TestCase {

	private ?EligibilityProvider $eligibilityProvider;
	private $eligibilityEndpointMock;
	private $cartAdapterMock;

	/**
	 * Given an empty cart (0 total), when retrieving eligibility, then the eligibility list should be empty and Api never called.
	 * @return void
	 * @throws EligibilityServiceException
	 * TODO : Need to move to FeePlanRepositoryTest
	 */
//	public function testRetrieveEligibilityWithCartOReturnEmptyEligibility() {
//		$this->cartAdapterMock->method( 'getCartTotal' )->willReturn( 0 );
//		$this->eligibilityEndpointMock->expects( $this->never() )->method( 'getEligibilityList' );
//
//		$this->assertNull( $this->eligibilityProvider->retrieveEligibility() );
//		$this->assertEquals( 0, $this->eligibilityProvider->getEligibilityList()->count() );
//	}

	/**
	 * Given a cart with a positive total,
	 * when retrieving eligibility and an error occurs in the API
	 * then an exception is thrown .
	 *
	 * @return void
	 * @throws EligibilityServiceException
	 */
	public function testRetrieveEligibilityApiThrowsException() {
		$this->expectException( EligibilityServiceException::class );
		$this->expectExceptionMessage( 'Error retrieving eligibility: API error' );

		$eligibilityDto = new EligibilityDto( 10000 );
		$this->eligibilityEndpointMock->expects( $this->once() )->method( 'getEligibilityList' )->willThrowException( new EligibilityEndpointException( 'API error' ) );

		$this->assertNull( $this->eligibilityProvider->retrieveEligibility( $eligibilityDto ) );
	}

	/**
	 * Given a cart with a positive total,
	 * when retrieving eligibility and the API returns a valid response
	 * then the eligibility list should be populated.
	 *
	 * @return void
	 * @throws EligibilityServiceException
	 */
	public function testRetrieveEligibilityApiReturnsValidResponse() {
		$eligibility     = $this->createMock( Eligibility::class );
		$eligibilityList = new EligibilityList();
		$eligibilityList->add( $eligibility );
		$this->eligibilityEndpointMock->expects( $this->once() )->method( 'getEligibilityList' )->willReturn( $eligibilityList );
		$eligibilityDto = new EligibilityDto( 10000 );

		$this->assertNull( $this->eligibilityProvider->retrieveEligibility($eligibilityDto) );
		$this->assertEquals( 1, $this->eligibilityProvider->getEligibilityList($eligibilityDto)->count() );

	}

	public function testGetEligibilityListCallApiInRetrieve() {
		$eligibility     = $this->createMock( Eligibility::class );
		$eligibilityList = new EligibilityList();
		$eligibilityList->add( $eligibility );
		$this->eligibilityEndpointMock->expects( $this->once() )->method( 'getEligibilityList' )->willReturn( $eligibilityList );
		$eligibilityDto = new EligibilityDto( 10000 );
		$eligibilityListResult = $this->eligibilityProvider->getEligibilityList($eligibilityDto);
		$this->assertInstanceOf( EligibilityList::class, $eligibilityListResult );
		$this->assertEquals( 1, $eligibilityListResult->count() );
	}

	public function testGetEligibilityListDoesNotCallApiIfAlreadyCalled() {
		$eligibility     = $this->createMock( Eligibility::class );
		$eligibilityList = new EligibilityList();
		$eligibilityList->add( $eligibility );
		$this->eligibilityEndpointMock->expects( $this->once() )->method( 'getEligibilityList' )->willReturn( $eligibilityList );
		$eligibilityDto = new EligibilityDto( 10000 );
		$eligibilityListResult1 = $this->eligibilityProvider->getEligibilityList($eligibilityDto);
		$this->assertInstanceOf( EligibilityList::class, $eligibilityListResult1 );
		$this->assertEquals( 1, $eligibilityListResult1->count() );
		$eligibilityListResult2 = $this->eligibilityProvider->getEligibilityList($eligibilityDto);
		$this->assertInstanceOf( EligibilityList::class, $eligibilityListResult2 );
		$this->assertEquals( 1, $eligibilityListResult2->count() );
	}

	protected function setUp(): void {
		$this->eligibilityEndpointMock = $this->createMock( EligibilityEndpoint::class );
		$this->cartAdapterMock         = $this->createMock( CartAdapter::class );
		$this->eligibilityProvider     = new EligibilityProvider( $this->eligibilityEndpointMock );
	}

	protected function tearDown(): void {
		$this->eligibilityProvider     = null;
		$this->eligibilityEndpointMock = null;
		$this->cartAdapterMock         = null;
	}

}
