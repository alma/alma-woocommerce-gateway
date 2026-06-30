<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Helper\InPageHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class InPageHelperTest extends TestCase {

	/** @var InPageHelper | null $inPageHelper */
	public ?InPageHelper $inPageHelper;

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

		$feePlanAdapter = $this->createMock( FeePlanAdapter::class );
		$feePlanAdapter->expects( $this->once() )
		               ->method( 'getPlanKey' )
		               ->willReturn( 'planKey_123' );
		$this->assertEquals(
			'https://woocommerce.com/?page_id=6&alma=inPage&pid=payment_123&planKey=planKey_123',
			$this->inPageHelper->getInPageRedirectionFallbackUrl( 'payment_123', $feePlanAdapter ) );
	}

	protected function setUp(): void {
		Monkey\setUp();
		$this->inPageHelper = new InPageHelper();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		$this->inPageHelper = null;
	}

}
