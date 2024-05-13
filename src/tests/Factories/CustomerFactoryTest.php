<?php
/**
 * Class CustomerFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\CustomerFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\CustomerFactory
 */
class CustomerFactoryTest extends WP_UnitTestCase {

	/**
	 * CustomerFactory.
	 *
	 * @var CustomerFactory $customer_factory
	 */
	protected $customer_factory;
	public function set_up() {
		$this->customer_factory = new CustomerFactory();
	}

	public function test_get_customer() {
		$this->assertInstanceOf(\WC_Customer::class, $this->customer_factory->get_customer());

		\WC()->customer = null;
		$this->assertNull( $this->customer_factory->get_customer());

	}

}



