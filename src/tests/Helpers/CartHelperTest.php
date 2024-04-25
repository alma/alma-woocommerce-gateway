<?php
/**
 * Class CartHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\CartHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CurrencyHelper;
use Alma\Woocommerce\Helpers\PriceHelper;
use Alma\Woocommerce\Helpers\SessionHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Helpers\VersionHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\CartHelper
 */
class CartHelperTest extends WP_UnitTestCase {
	/**
	 * The session helper.
	 *
	 * @var SessionHelper
	 */
	protected $session_helper;

	/**
	 * The version helper.
	 *
	 * @var VersionHelper
	 */
	protected $version_helper;


	/**
	 * The tools helper.
	 *
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	public function setUp() {
		$this->session_helper = new SessionHelper();
		$this->version_helper = new VersionHelper();
		$this->tools_helper = new ToolsHelper(new AlmaLogger(), new PriceHelper(), new CurrencyHelper());
	}
	/**
	 * @covers \Alma\Woocommerce\Helpers\CartHelper::get_total_from_cart
	 *
	 * @return void
	 */
	public function test_get_total_from_cart() {
		// Test Empty Cart
		$cart_helper = \Mockery::mock(CartHelper::class, [
			$this->tools_helper,
			$this->session_helper,
			$this->version_helper
		])->makePartial();
		$cart_helper->shouldReceive('get_cart')
		        ->andReturn(null);

		$this->assertEquals('0', $cart_helper->get_total_from_cart());


		// Test Cart version < 3.2.0
		$version_helper = \Mockery::mock(VersionHelper::class);
		$version_helper->shouldReceive('get_version')
			->andReturn('2.0.0');

		$cart_helper = \Mockery::mock(CartHelper::class, [$this->tools_helper, $this->session_helper, $version_helper])->makePartial();
		$cart = new \stdClass();
		$cart->total = '1.0000';

		$cart_helper->shouldReceive('get_cart')
		           ->andReturn($cart);

		$this->assertEquals('1.0000', $cart_helper->get_total_from_cart());

		// Test Cart version >3.2.0 and cart total not null

		$cart_helper = \Mockery::mock(CartHelper::class, [$this->tools_helper, $this->session_helper, $this->version_helper])->makePartial();

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

		$cart_helper = \Mockery::mock(CartHelper::class, [$this->tools_helper, $session_helper, $this->version_helper])->makePartial();


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

		$cart_helper = \Mockery::mock(CartHelper::class, [$this->tools_helper,$session_helper, $this->version_helper])->makePartial();

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
		$cart_helper = \Mockery::mock(CartHelper::class, [$this->tools_helper, $this->session_helper, $this->version_helper])->makePartial();
		$cart_helper->shouldReceive('get_total_from_cart')
		            ->andReturn('4.000');

		$this->assertEquals('400', $cart_helper->get_total_in_cents());
	}
}



