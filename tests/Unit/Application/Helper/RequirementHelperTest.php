<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Exception\Helper\RequirementsHelperException;
use Alma\Gateway\Application\Helper\RequirementsHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RequirementHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $requirementHelper;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->requirementHelper = new RequirementsHelper();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
		Mockery::close();

	}

	public function testWCnotExit() {
		Functions\expect( '__' )
			->once()
			->with( 'Alma requires WooCommerce to be activated', 'alma-gateway-for-woocommerce' )
			->andReturn( 'Translated error message' );
		$this->expectException( RequirementsHelperException::class );
		$this->requirementHelper->check_dependencies( '7.0.0' );
	}

	public function testCompareVersionLowerThan7WillThrow() {
		Functions\expect( 'WC' )
			->andReturn( function () {
			} );

		Functions\expect( '__' )
			->once()
			->with( 'Alma requires WooCommerce version 7.0.0 or greater', 'alma-gateway-for-woocommerce' )
			->andReturn( 'Translated error message' );

		$this->expectException( RequirementsHelperException::class );
		$this->requirementHelper->check_dependencies( '6.9.9' );
	}


	public function testRequirementOk(): void {
		Functions\expect( 'WC' )
			->andReturn( function () {
			} );
		$this->assertTrue( $this->requirementHelper->check_dependencies( '7.0.0' ) );

	}

}