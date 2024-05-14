<?php
/**
 * Class PHPFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\PHPFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\PHPFactory
 */
class PHPFactoryTest extends WP_UnitTestCase {

	/**
	 * PHPFactory.
	 *
	 * @var PHPFactory $php_factory
	 */
	protected $php_factory;
	public function set_up() {
		$this->php_factory = new PHPFactory();
	}

	public function test_method_exists() {
		$customer = new \WC_Customer();
		$this->assertTrue($this->php_factory->method_exists($customer, 'get_first_name'));
		$this->assertFalse($this->php_factory->method_exists($customer, 'get_my_great_customer_id'));
	}

}



