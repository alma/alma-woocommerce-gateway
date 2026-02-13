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

	public function testWCnotExit() {
		$this->expectException( RequirementsHelperException::class );
		$this->requirementHelper::check_dependencies( '5.0.0', '7.0.0' );
	}

	public function testCompareVersionLowerThanExpectedWillThrow() {
		$this->expectException( RequirementsHelperException::class );
		$this->requirementHelper::check_dependencies( '5.0.0', '6.9.9' );
	}

	public function testRequirementOk(): void {
		Functions\expect( 'WC' )
			->andReturn( function () {
			} );
		$this->assertTrue( $this->requirementHelper::check_dependencies( '6.6', '10.1.0' ) );
	}

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

}
