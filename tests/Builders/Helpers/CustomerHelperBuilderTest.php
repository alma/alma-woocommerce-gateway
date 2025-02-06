<?php
/**
 * Class CartHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Helpers\CartHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Helpers;


use Alma\Woocommerce\Builders\Helpers\CustomerHelperBuilder;
use Alma\Woocommerce\Factories\CustomerFactory;
use Alma\Woocommerce\Factories\PHPFactory;
use Alma\Woocommerce\Helpers\CustomerHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\CustomerHelperBuilder
 */
class CustomerHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The customer helper builder.
	 *
	 * @var CustomerHelperBuilder $customer_helper_builder
	 */
	protected $customer_helper_builder;

	public function set_up() {
		$this->customer_helper_builder = new CustomerHelperBuilder();
	}

	public function test_get_instance() {
		$this->assertInstanceOf(CustomerHelper::class, $this->customer_helper_builder->get_instance());
	}

	public function test_get_customer_factory() {
		$this->assertInstanceOf(CustomerFactory::class, $this->customer_helper_builder->get_customer_factory());
		$this->assertInstanceOf(CustomerFactory::class, $this->customer_helper_builder->get_customer_factory(
			new CustomerFactory(new PHPFactory())
		));
	}
}



