<?php

namespace Alma\Gateway\Tests\Unit\Application\Entity\Form;

use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Entity\Form\FeePlanConfiguration;
use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use PHPUnit\Framework\TestCase;

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
			new \stdClass(), // This should be filtered out
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

	/**
	 * Test that there are no errors initially.
	 */
	public function testValidationAndGetErrors() {
		$this->assertCount( 0, $this->feePlanConfigurationList->getErrors() );
		$this->feePlanConfigurationList->validate( $this->feePlanAdapterList );
		$this->assertCount( 3, $this->feePlanConfigurationList->getErrors() );
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
