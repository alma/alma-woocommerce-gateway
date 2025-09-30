<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\AlmaHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AlmaHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testGetAlmaDashboardUrlSandboxDefault() {
		Functions\expect( 'esc_url' )
			->once()
			->withArgs( function ( $url, $protocols, $_context ) {
				$this->assertSame( 'https://dashboard.sandbox.getalma.eu/', $url );
				$this->assertSame( null, $protocols );
				$this->assertSame( 'display', $_context );

				return true;
			} )
			->andReturn( 'finalUrl' );

		AlmaHelper::getAlmaDashboardUrl();
	}

	public function testGetAlmaDashboardUrlSandboxWithParam() {
		Functions\expect( 'esc_url' )
			->once()
			->withArgs( function ( $url, $protocols, $_context ) {
				$this->assertSame( 'https://dashboard.sandbox.getalma.eu/mypath', $url );
				$this->assertSame( null, $protocols );
				$this->assertSame( 'display', $_context );

				return true;
			} )
			->andReturn( 'finalUrl' );

		AlmaHelper::getAlmaDashboardUrl( 'test', 'mypath' );
	}

	public function testGetAlmaDashboardUrlLive() {
		Functions\expect( 'esc_url' )
			->once()
			->withArgs( function ( $url, $protocols, $_context ) {
				$this->assertSame( 'https://dashboard.getalma.eu/mypath', $url );
				$this->assertSame( null, $protocols );
				$this->assertSame( 'display', $_context );

				return true;
			} )
			->andReturn( 'finalUrl' );

		AlmaHelper::getAlmaDashboardUrl( 'live', 'mypath' );
	}


}