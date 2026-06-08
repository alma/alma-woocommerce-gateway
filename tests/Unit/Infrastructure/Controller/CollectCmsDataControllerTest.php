<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Controller;

use Alma\Gateway\Application\Service\CollectCmsDataService;
use Alma\Gateway\Infrastructure\Controller\CollectCmsDataController;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class CollectCmsDataControllerTest extends TestCase {

	private CollectCmsDataService $mockService;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->mockService = $this->createMock( CollectCmsDataService::class );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * configure() must register the correct WooCommerce API hook name.
	 */
	public function testConfigureRegistersCorrectHookName(): void {
		$capturedHook = null;

		Functions\when( 'did_action' )->justReturn( false );
		Functions\when( 'add_action' )->alias(
			function ( $hook ) use ( &$capturedHook ) {
				$capturedHook = $hook;
			}
		);

		CollectCmsDataController::configure( $this->mockService );

		$this->assertSame(
			'woocommerce_api_' . CollectCmsDataService::WC_API_ENDPOINT,
			$capturedHook
		);
	}

	/**
	 * configure() must register handle() from CollectCmsDataService as the callback.
	 */
	public function testConfigureRegistersHandleAsCallback(): void {
		$capturedCallback = null;

		Functions\when( 'did_action' )->justReturn( false );
		Functions\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$capturedCallback ) {
				$capturedCallback = $callback;
			}
		);

		CollectCmsDataController::configure( $this->mockService );

		$this->assertIsArray( $capturedCallback );
		$this->assertSame( $this->mockService, $capturedCallback[0] );
		$this->assertSame( 'handle', $capturedCallback[1] );
	}
}
