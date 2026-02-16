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

	public function testWCnotExit() {
		$this->expectException( RequirementsHelperException::class );
		RequirementsHelper::check_dependencies( '5.0.0', '7.0.0' );
	}

	public function testCompareVersionLowerThanExpectedWillThrow() {
		$this->expectException( RequirementsHelperException::class );
		RequirementsHelper::check_dependencies( '5.0.0', '6.9.9' );
	}

	public function testRequirementOk(): void {
		define( 'WC_VERSION', 'ok' );
		$this->assertTrue( RequirementsHelper::check_dependencies( '6.6', '10.1.0' ) );
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
		Mockery::close();

	}

}
