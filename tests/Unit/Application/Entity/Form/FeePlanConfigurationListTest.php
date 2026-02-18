<?php

namespace Alma\Gateway\Tests\Unit\Application\Entity\Form;

use Alma\Client\Domain\Entity\FeePlan;
use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Entity\Form\FeePlanConfiguration;
use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use PHPUnit\Framework\TestCase;
use stdClass;

class FeePlanConfigurationListTest extends TestCase {

	/** @var FeePlanConfigurationList $feePlanConfigurationList */
	private FeePlanConfigurationList $feePlanConfigurationList;
	private FeePlanListAdapter $feePlanAdapterList;

	public function setUp(): void {
		// Create a mix of valid and invalid FeePlanConfiguration objects
		$firstFeePlanConfigurationMock = $this->createMock( FeePlanConfiguration::class );
		$firstFeePlanConfigurationMock->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$firstFeePlanConfigurationMock->method( 'getErrors' )->willReturn( [ 'first error', 'second error' ] );

		$secondFeePlanConfigurationMock = $this->createMock( FeePlanConfiguration::class );
		$secondFeePlanConfigurationMock->method( 'getPlanKey' )->willReturn( 'general_3_0_0' );
		$secondFeePlanConfigurationMock->method( 'getErrors' )->willReturn( [ 'third error' ] );

		$feePlanConfigurationArray      = [
			$firstFeePlanConfigurationMock,
			$secondFeePlanConfigurationMock,
			new stdClass(), // This should be filtered out
		];
		$this->feePlanConfigurationList = new FeePlanConfigurationList( $feePlanConfigurationArray );

		// Prepare FeePlans
		$firstFeePlanMock = $this->createMock( FeePlan::class );
		$firstFeePlanMock->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$firstFeePlanMock->method( 'getMinPurchaseAmount' )->willReturn( 5000 );
		$firstFeePlanMock->method( 'getMaxPurchaseAmount' )->willReturn( 200000 );

		$secondFeePlanMock = $this->createMock( FeePlan::class );
		$secondFeePlanMock->method( 'getPlanKey' )->willReturn( 'general_3_0_0' );
		$secondFeePlanMock->method( 'getMinPurchaseAmount' )->willReturn( 5000 );
		$secondFeePlanMock->method( 'getMaxPurchaseAmount' )->willReturn( 200000 );

		// Mock the FeePlanRepository
		$this->feePlanAdapterList = new FeePlanListAdapter( new FeePlanList( [
			$firstFeePlanMock,
			$secondFeePlanMock
		] ) );

	}

	/**
	 * Test that only valid FeePlanConfiguration objects are retained.
	 */
	public function testInvalidFeePlanObject() {
		$this->assertCount( 2, $this->feePlanConfigurationList );
	}

	public function testValidateWithMatchingPlanKeyAddsErrors() {
		$feePlanAdapter = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapter->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );

		$feePlanListAdapter = new FeePlanListAdapter( [ $feePlanAdapter ] );

		$feePlan = $this->createMock( FeePlanConfiguration::class );
		$feePlan->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$feePlan->method( 'getErrors' )->willReturn( [ 'error1' ] );
		$feePlan->expects( $this->once() )->method( 'validate' )->with( $feePlanAdapter );

		$feePlanList = new FeePlanConfigurationList( [ $feePlan ] );
		$feePlanList->validate( $feePlanListAdapter );

		$this->assertEquals( [ 'error1' ], $feePlanList->getErrors() );
	}

	public function testValidateWithNoMatchingPlanKeyDoesNotAddErrors() {
		$feePlanAdapter = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapter->method( 'getPlanKey' )->willReturn( 'general_3_0_0' );

		$feePlanListAdapter = new FeePlanListAdapter( [ $feePlanAdapter ] );

		$feePlan = $this->createMock( FeePlanConfiguration::class );
		$feePlan->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$feePlan->method( 'getErrors' )->willReturn( [] );
		$feePlan->expects( $this->never() )->method( 'validate' );

		$feePlanList = new FeePlanConfigurationList( [ $feePlan ] );
		$feePlanList->validate( $feePlanListAdapter );

		$this->assertEquals( [], $feePlanList->getErrors() );
	}

	/**
	 * Test the reset functionality.
	 */
	public function testReset() {
		$this->assertCount( 2, $this->feePlanConfigurationList );
		$this->feePlanConfigurationList->reset();
		$this->assertCount( 0, $this->feePlanConfigurationList );
	}
}
