<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Tests\Unit\Mocks\FeePlanMock;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class L10nHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $l10nHelper;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
		Mockery::close();

	}

	/**
	 * Return error if with args are not the expected ones
	 *\LanguageHelper::__() must be of the type string, null returned
	 * @return void
	 */
	public function testTranslate(): void {
		Functions\expect( '__' )
			->once()
			->with( 'test text', 'alma-gateway-for-woocommerce' )
			->andReturn( 'translated' );

		$result = L10nHelper::__( 'test text' );
		$this->assertEquals( 'translated', $result );
	}

	public function testLoadLanguageWithCallbackExecution(): void {
		$capturedCallback = null;

		Functions\expect( 'add_action' )
			->once()
			->withArgs( function ( $event, $callback ) use ( &$capturedCallback ) {
				$this->assertSame( 'plugins_loaded', $event );
				$this->assertIsCallable( $callback );

				// Capture callback
				$capturedCallback = $callback;

				return true;
			} );

		// Mock load_plugin_textdomain for callback execution
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with( 'alma-gateway-for-woocommerce', false, 'path/to/plugin/languages' );


		L10nHelper::load_language( 'path/to/plugin' );

		// Manually execute the captured callback to simulate 'plugins_loaded' action
		$this->assertNotNull( $capturedCallback, 'Callback should have been captured' );
		$capturedCallback();
	}

	/**
	 * @TODO Need to move this transtlation to the FeePlan Object?
	 *
	 * @return void
	 */
	public function testGenerateFeePlanDisplayData(): void {
		Functions\expect( '__' )
			->andReturn( 'translated string' );
		Functions\expect( 'esc_url' )
			->andReturn( 'escapedUrl' );

		$feePlan     = FeePlanMock::getFeePlan();
		$displayData = L10nHelper::generate_fee_plan_display_data( $feePlan, 'test' );

		$this->assertIsArray( $displayData );
		$this->assertArrayHasKey( 'title', $displayData );
		$this->assertArrayHasKey( 'toggle_label', $displayData );
		$this->assertArrayHasKey( 'description', $displayData );
	}

}

