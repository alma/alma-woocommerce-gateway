<?php
/**
 * Class PriceFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\PriceFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\PriceFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\PriceFactory
 */
class PriceFactoryTest extends WP_UnitTestCase {

	/**
	 * PriceFactory.
	 *
	 * @var PriceFactory $price_factory
	 */
	protected $price_factory;
	public function set_up() {
		$this->price_factory = new PriceFactory();
	}

	public function test_get_woo_decimal_separator() {
		$this->assertEquals('.', $this->price_factory->get_woo_decimal_separator());
	}

	public function test_get_woo_thousand_separator() {
		$this->assertEquals(',', $this->price_factory->get_woo_thousand_separator());
	}

	public function test_get_woo_decimals() {
		$this->assertEquals('2', $this->price_factory->get_woo_decimals());
	}

	public function test_get_get_woo_format() {
		$this->assertEquals('%1$s%2$s', $this->price_factory->get_woo_format());
	}
}



