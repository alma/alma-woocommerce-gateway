<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Helper\InPageHelper;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class InPageHelperTest extends TestCase {

	/** @var InPageHelper | null $inPageHelper */
	public ?InPageHelper $inPageHelper;

	protected function setUp(): void {
		Monkey\setUp();
		$this->inPageHelper = new InPageHelper();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		$this->inPageHelper = null;
	}


	public function testGetInPageRedirectionUrl() {

		Functions\expect( 'wc_get_checkout_url' )
			->once()
			->andReturn( 'https://woocommerce.com/?page_id=6' );

		Functions\expect( 'add_query_arg' )
			->once()
			->withArgs( function ( $args, $url ) {
				$this->assertIsArray( $args );
				$this->assertArrayHasKey( 'alma', $args );
				$this->assertArrayHasKey( 'pid', $args );
				$this->assertSame( 'inPage', $args['alma'] );
				$this->assertSame( 'payment_123', $args['pid'] );
				$this->assertSame( 'https://woocommerce.com/?page_id=6', $url );

				return true;
			} )
			->andReturnUsing( function ( $args, $url ) {
				return $url . '&' . http_build_query( $args );
			} );

		$this->assertEquals(
			'https://woocommerce.com/?page_id=6&alma=inPage&pid=payment_123',
			$this->inPageHelper->getInPageRedirectionFallbackUrl( 'payment_123' ) );
	}

}
