<?php
/**
 * Class CartHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\CartHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\SessionHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Helpers\VersionHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\CartHelper
 */
class CartHelperTest extends WP_UnitTestCase {

	/**
	 * @covers \Alma\Woocommerce\Helpers\CartHelper::get_total_from_cart
	 *
	 * @return void
	 */
	public function test_get_total_from_cart() {
		// Test Empty Cart
		$cart_helper = \Mockery::mock(CartHelper::class, [new ToolsHelper(), new SessionHelper(), new VersionHelper()])->makePartial();
		$cart_helper->shouldReceive('get_cart')
		        ->andReturn(null);

		$this->assertEquals('0', $cart_helper->get_total_from_cart());


		// Test Cart version < 3.2.0
		$version_helper = \Mockery::mock(VersionHelper::class);
		$version_helper->shouldReceive('get_version')
			->andReturn('2.0.0');

		$cart_helper = \Mockery::mock(CartHelper::class, [new ToolsHelper(), new SessionHelper(), $version_helper])->makePartial();
		$cart = new \stdClass();
		$cart->total = '1.0000';

		$cart_helper->shouldReceive('get_cart')
		           ->andReturn($cart);

		$this->assertEquals('1.0000', $cart_helper->get_total_from_cart());

		// Test Cart version >3.2.0 and cart total not null

		$cart_helper = \Mockery::mock(CartHelper::class, [new ToolsHelper(), new SessionHelper(), new VersionHelper()])->makePartial();

		$wc_cart = \Mockery::mock(\WC_Cart::class);
		$wc_cart->shouldReceive('get_total')
		              ->andReturn('2.0000');

		$cart_helper->shouldReceive('get_cart')
		           ->andReturn($wc_cart);

		$this->assertEquals('2.0000', $cart_helper->get_total_from_cart());


		// Test Cart version >3.2.0 and cart total null and session cart_totals null
		$session = \Mockery::mock(\WC_Session::class);
		$session->shouldReceive('get', ['cart_totals'])
		        ->andReturn(null);

		$session_helper = \Mockery::mock(SessionHelper::class);
		$session_helper->shouldReceive('get_session')
		               ->andReturn($session);

		$cart_helper = \Mockery::mock(CartHelper::class, [new ToolsHelper(), $session_helper, new VersionHelper()])->makePartial();


		$wc_cart = \Mockery::mock(\WC_Cart::class);
		$wc_cart->shouldReceive('get_total')
		        ->andReturn('0');

		$cart_helper->shouldReceive('get_cart')
		           ->andReturn($wc_cart);

		$this->assertEquals(0, $cart_helper->get_total_from_cart());

		// Test Cart version >3.2.0 and cart total null and session cart_totals not null
		$session = \Mockery::mock(\WC_Session::class);
		$session->shouldReceive('get', ['cart_totals'])
		         ->andReturn(array('total' => '3.000'));

		$session_helper = \Mockery::mock(SessionHelper::class);
		$session_helper->shouldReceive('get_session')
		               ->andReturn($session);

		$cart_helper = \Mockery::mock(CartHelper::class, [new ToolsHelper(),$session_helper, new VersionHelper()])->makePartial();

		$wc_cart = \Mockery::mock(\WC_Cart::class);
		$wc_cart->shouldReceive('get_total')
		        ->andReturn('0');

		$cart_helper->shouldReceive('get_cart')
		            ->andReturn($wc_cart);

		$this->assertEquals('3.000', $cart_helper->get_total_from_cart());

	}

	/**
	 *
	 * @covers \Alma\Woocommerce\Helpers\CartHelper::get_total_in_cents
	 *
	 * @return void
	 */
	public function test_get_total_in_cents() {
		$cart_helper = \Mockery::mock(CartHelper::class, [new ToolsHelper(), new SessionHelper(), new VersionHelper()])->makePartial();
		$cart_helper->shouldReceive('get_total_from_cart')
		            ->andReturn('4.000');

		$this->assertEquals('400', $cart_helper->get_total_in_cents());
	}
}



