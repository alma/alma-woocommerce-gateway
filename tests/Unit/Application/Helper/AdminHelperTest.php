<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\AdminHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AdminHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testCanManageAlmaErrorWithDefaultText() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Forbidden, current user don\'t have rights.' ], $response );
				$this->assertSame( 403, $code );

				return true;
			} );

		AdminHelper::canManageAlmaError();
	}

	public function testCanManageAlmaErrorWithCustomText() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Test message' ], $response );
				$this->assertSame( 403, $code );

				return true;
			} );

		AdminHelper::canManageAlmaError( 'Test message' );
	}

	public function testSuccessWithTrueData() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'success' => true, 'data' => true ], $response );
				$this->assertSame( 200, $code );

				return true;
			} );

		AdminHelper::success( true );
	}

	public function testSuccessWithFalseData() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'success' => true, 'data' => false ], $response );
				$this->assertSame( 200, $code );

				return true;
			} );

		AdminHelper::success( false );
	}

}