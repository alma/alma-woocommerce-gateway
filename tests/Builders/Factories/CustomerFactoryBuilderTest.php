<?php
/**
 * Class CartFactoryBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Factories\CustomerFactoryBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Factories;


use Alma\Woocommerce\Builders\Factories\CustomerFactoryBuilder;
use Alma\Woocommerce\Factories\CustomerFactory;
use Alma\Woocommerce\Factories\PHPFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Factories\CustomerFactoryBuilder
 */
class CustomerFactoryBuilderTest extends WP_UnitTestCase {

	/**
	 * The customer factory builder.
	 *
	 * @var CustomerFactoryBuilder $customer_factory_builder
	 */
	protected $customer_factory_builder;

	public function set_up() {
		$this->customer_factory_builder = new CustomerFactoryBuilder();
	}

	public function test_get_instance() {
		$this->assertInstanceOf(CustomerFactory::class, $this->customer_factory_builder->get_instance());
	}

	public function test_get_php_factory() {
		$this->assertInstanceOf(PHPFactory::class, $this->customer_factory_builder->get_php_factory());
		$this->assertInstanceOf(PHPFactory::class, $this->customer_factory_builder->get_php_factory( new PHPFactory()) );
	}
}



