<?php
/**
 * Class CoreFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\CoreFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\CoreFactory
 */
class CoreFactoryTest extends WP_UnitTestCase {

	/**
	 * CoreFactory.
	 *
	 * @var CoreFactory $core_factory
	 */
	protected $core_factory;
	public function set_up() {
		$this->core_factory = new CoreFactory();
	}

	public function test_has_term()
	{
		$this->assertFalse($this->core_factory->has_term('test', 'product_cat', 1));
	}
}



