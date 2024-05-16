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
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\SessionFactory;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CustomerHelper;
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
		$cart_factory = \Mockery::mock(CartFactory::class);
		$cart_factory->shouldReceive('get_cart')
		             ->andReturn(null);
		$cart_helper_builder = \Mockery::mock(CartHelperBuilder::class)->makePartial();
		$cart_helper_builder->shouldReceive('get_cart_factory')
				->andReturn($cart_factory);

		$cart_helper = $cart_helper_builder->get_instance();

		$this->assertEquals('0', $cart_helper->get_total_from_cart());

		// Test Cart version < 3.2.0
		$version_factory = \Mockery::mock(VersionFactory::class);
		$version_factory->shouldReceive('get_version')
			->andReturn('2.0.0');

		$cart = new \stdClass();
		$cart->total = '1.0000';

		$cart_factory = \Mockery::mock(CartFactory::class);
		$cart_factory->shouldReceive('get_cart')
		             ->andReturn($cart);

		$cart_helper_builder = \Mockery::mock(CartHelperBuilder::class)->makePartial();
		$cart_helper_builder->shouldReceive('get_cart_factory')
		                    ->andReturn($cart_factory);
		$cart_helper_builder->shouldReceive('get_version_factory')
		                    ->andReturn($version_factory);

		$cart_helper = $cart_helper_builder->get_instance();

		$this->assertEquals('1.0000', $cart_helper->get_total_from_cart());

		// Test Cart version >3.2.0 and cart total not null

		$wc_cart = \Mockery::mock(\WC_Cart::class);
		$wc_cart->shouldReceive('get_total')
		        ->andReturn('2.0000');

		$cart_factory = \Mockery::mock(CartFactory::class);
		$cart_factory->shouldReceive('get_cart')
		             ->andReturn($wc_cart);

		$cart_helper_builder = \Mockery::mock(CartHelperBuilder::class)->makePartial();
		$cart_helper_builder->shouldReceive('get_cart_factory')
		                    ->andReturn($cart_factory);

		$cart_helper = $cart_helper_builder->get_instance();

		$this->assertEquals('2.0000', $cart_helper->get_total_from_cart());


		// Test Cart version >3.2.0 and cart total null and session cart_totals null
		$session = \Mockery::mock(\WC_Session::class);
		$session->shouldReceive('get', ['cart_totals'])
		        ->andReturn(null);

		$session_factory = \Mockery::mock(SessionFactory::class);
		$session_factory->shouldReceive('get_session')
		               ->andReturn($session);


		$wc_cart = \Mockery::mock(\WC_Cart::class);
		$wc_cart->shouldReceive('get_total')
		        ->andReturn('0');

		$cart_factory = \Mockery::mock(CartFactory::class);
		$cart_factory->shouldReceive('get_cart')
		             ->andReturn($wc_cart);

		$cart_helper_builder = \Mockery::mock(CartHelperBuilder::class)->makePartial();
		$cart_helper_builder->shouldReceive('get_cart_factory')
		                    ->andReturn($cart_factory);
		$cart_helper_builder->shouldReceive('get_session_factory')
		                    ->andReturn($session_factory);

		$cart_helper = $cart_helper_builder->get_instance();


		$this->assertEquals(0, $cart_helper->get_total_from_cart());

		// Test Cart version >3.2.0 and cart total null and session cart_totals not null
		$session = \Mockery::mock(\WC_Session::class);
		$session->shouldReceive('get', ['cart_totals'])
		         ->andReturn(array('total' => '3.000'));

		$session_factory = \Mockery::mock(SessionFactory::class);
		$session_factory->shouldReceive('get_session')
		               ->andReturn($session);

		$wc_cart = \Mockery::mock(\WC_Cart::class);
		$wc_cart->shouldReceive('get_total')
		        ->andReturn('0');

		$cart_factory = \Mockery::mock(CartFactory::class);
		$cart_factory->shouldReceive('get_cart')
		             ->andReturn($wc_cart);

		$cart_helper_builder = \Mockery::mock(CartHelperBuilder::class)->makePartial();
		$cart_helper_builder->shouldReceive('get_cart_factory')
		                    ->andReturn($cart_factory);
		$cart_helper_builder->shouldReceive('get_session_factory')
		                    ->andReturn($session_factory);

		$cart_helper = $cart_helper_builder->get_instance();


		$this->assertEquals('3.000', $cart_helper->get_total_from_cart());

	}

	/**
	 *
	 * @covers \Alma\Woocommerce\Helpers\CartHelper::get_total_in_cents
	 *
	 * @return void
	 */
	public function test_get_total_in_cents() {
		$tools_helper_builder = new ToolsHelperBuilder();
		$tools_helper = $tools_helper_builder->get_tools_helper();

		$cart_helper = \Mockery::mock(
			CartHelper::class,
			[
				$tools_helper,
				new SessionFactory(),
				new VersionFactory(),
				new CartFactory(),
				new AlmaSettings(),
				new AlmaLogger(),
				\Mockery::mock(CustomerHelper::class)
			])->makePartial();
		$cart_helper->shouldReceive('get_total_from_cart')
		            ->andReturn('4.000');

		$this->assertEquals('400', $cart_helper->get_total_in_cents());
	}
}



