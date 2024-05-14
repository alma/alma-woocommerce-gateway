<?php
/**
 * Class CartFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CartFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;


use Alma\Woocommerce\Factories\CartFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\CartFactory
 */
class CartFactoryTest extends WP_UnitTestCase {

	/**
	 * Cartfactory.
	 *
	 * @var CartFactory $cart_factory
	 */
	protected $cart_factory;
	public function set_up() {
		$this->cart_factory = new CartFactory();
	}

	public function test_get_cart() {
		$this->assertInstanceOf(\WC_Cart::class, $this->cart_factory->get_cart());
	}

	public function test_get_cart_items() {
		$cart_factory = \Mockery::mock(CartFactory::class)->makePartial();
		$cart_factory->shouldReceive('get_cart')->andReturn(null);

		$this->assertEquals(array(), $this->cart_factory->get_cart_items());
	}

}



