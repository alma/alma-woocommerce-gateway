<?php

namespace Alma\Gateway\Tests\Unit\Application\Entity\Form;

use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Entity\Form\FeePlanConfiguration;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use PHPUnit\Framework\TestCase;

class FeePlanConfigurationTest extends TestCase {

	private const MIN_PURCHASE_AMOUNT = 5000;
	private const MAX_PURCHASE_AMOUNT = 200000;

	private FeePlanConfiguration $feePlanConfiguration;
	private FeePlanConfiguration $badFeePlanConfiguration;

	public function setUp(): void {
		$this->feePlanConfiguration = new FeePlanConfiguration(
			'general_2_0_0',
			self::MIN_PURCHASE_AMOUNT,
			self::MAX_PURCHASE_AMOUNT,
			true
		);

		$this->badFeePlanConfiguration = new FeePlanConfiguration(
			'general_2_0_0',
			190000,
			6000,
			true
		);
	}

	public function testGetters() {
		$this->assertEquals( 'general_2_0_0', $this->feePlanConfiguration->getPlanKey() );
		$this->assertEquals( self::MIN_PURCHASE_AMOUNT, $this->feePlanConfiguration->getMinAmount() );
		$this->assertEquals( self::MAX_PURCHASE_AMOUNT, $this->feePlanConfiguration->getMaxAmount() );
		$this->assertTrue( $this->feePlanConfiguration->isEnabled() );
	}

	/**
	 * Test the validate method with valid data.
	 */
	public function testValidate() {
		$feePlanAdapterMock = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapterMock->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$feePlanAdapterMock->method( 'getMinPurchaseAmount' )->willReturn( self::MIN_PURCHASE_AMOUNT );
		$feePlanAdapterMock->method( 'getMaxPurchaseAmount' )->willReturn( self::MAX_PURCHASE_AMOUNT );

		$this->feePlanConfiguration->validate( $feePlanAdapterMock );
		$this->assertEmpty( $this->feePlanConfiguration->getErrors() );
	}

	/**
	 * Test the validate method with invalid data.
	 */
	public function testValidateWithMinOverrideOverMaxOverrideErrors() {
		$feePlanAdapterMock = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapterMock->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$feePlanAdapterMock->method( 'getMinPurchaseAmount' )->willReturn( 6000 );
		$feePlanAdapterMock->method( 'getMaxPurchaseAmount' )->willReturn( 150000 );
		$feePlanAdapterMock->method( 'setOverrideMinPurchaseAmount' )
		                   ->willThrowException( new ParametersException( 'The minimum purchase amount cannot be lower than the minimum allowed by Alma.' ) );
		$feePlanAdapterMock->method( 'setOverrideMaxPurchaseAmount' )
		                   ->willThrowException( new ParametersException( 'The maximum purchase amount cannot be higher than the maximum allowed by Alma.' ) );

		$this->feePlanConfiguration->validate( $feePlanAdapterMock );
		$errors = $this->feePlanConfiguration->getErrors();
		$this->assertCount( 2, $errors );
		$this->assertContains( 'The minimum purchase amount cannot be lower than the minimum allowed by Alma.',
			$errors );
		$this->assertContains( 'The maximum purchase amount cannot be higher than the maximum allowed by Alma.',
			$errors );
	}

	/**
	 * Test the validate method with invalid data.
	 */
	public function testValidateWithMinOverMaxErrors() {
		$feePlanAdapterMock = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapterMock->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$feePlanAdapterMock->method( 'getMinPurchaseAmount' )->willReturn( self::MIN_PURCHASE_AMOUNT );
		$feePlanAdapterMock->method( 'getMaxPurchaseAmount' )->willReturn( self::MAX_PURCHASE_AMOUNT );
		$feePlanAdapterMock->method( 'setOverrideMinPurchaseAmount' )
		                   ->willThrowException( new ParametersException( 'The minimum purchase amount cannot be higher than the maximum.' ) );
		$feePlanAdapterMock->method( 'setOverrideMaxPurchaseAmount' )
		                   ->willThrowException( new ParametersException( 'The minimum purchase amount cannot be higher than the maximum.' ) );

		$this->badFeePlanConfiguration->validate( $feePlanAdapterMock );
		$errors = $this->badFeePlanConfiguration->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertContains( 'The minimum purchase amount cannot be higher than the maximum.',
			$errors );

	}

}