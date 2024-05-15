<?php
/**
 * Class CurrencyFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\CurrencyFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 */
class CurrencyFactoryTest extends WP_UnitTestCase {

	/**
	 * CurrencyFactory.
	 *
	 * @var CurrencyFactory $currency_factory
	 */
	protected $currency_factory;
	public function set_up() {
		$this->currency_factory = new CurrencyFactory();
	}

	public function test_get_currency() {
		$this->assertEquals('USD', $this->currency_factory->get_currency());
	}

}



