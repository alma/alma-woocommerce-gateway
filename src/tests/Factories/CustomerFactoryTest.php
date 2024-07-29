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
use Alma\Woocommerce\Factories\PHPFactory;
use Mockery;
use WC_Customer;
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

	/**
	 * \WC_Customer .
	 *
	 * @var \WC_Customer $customer
	 */
	protected $customer;

	public function set_up() {
		$this->customer_factory = new CustomerFactory(new PHPFactory());

		$this->customer = new WC_Customer();
		$this->customer->set_first_name('FirstName');
		$this->customer->set_last_name('LastName');
		$this->customer->set_email('firstname@alma.com');
		$this->customer->set_billing_phone('33000000000');
		$this->customer->set_billing_first_name('BillingFirstName');
		$this->customer->set_shipping_first_name('ShippingFirstName');
		$this->customer->set_billing_last_name('BillingLastName');
		$this->customer->set_shipping_last_name('ShippingLastName');
		$this->customer->set_billing_address('BillingAddress');
		$this->customer->set_shipping_address('ShippingAddress');
		$this->customer->set_billing_address_2('BillingAddress2');
		$this->customer->set_shipping_address_2('ShippingAddress2');
		$this->customer->set_billing_postcode('BillingPostcode');
		$this->customer->set_shipping_postcode('ShippingPostcode');
		$this->customer->set_billing_city('BillingCity');
		$this->customer->set_shipping_city('ShippingCity');
		$this->customer->set_billing_country('BillingCountry');
		$this->customer->set_shipping_country('ShippingCountry');
		$this->customer->set_billing_email('billingemail@alma.com');
	}

	public function test_get_customer() {
		$this->assertInstanceOf(\WC_Customer::class, $this->customer_factory->get_customer());

		\WC()->customer = null;
		$this->assertNull( $this->customer_factory->get_customer());

	}

	public function test_get_first_name() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_first_name());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('FirstName', $customer_factory->get_first_name());
	}

	public function test_get_last_name() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_last_name());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('LastName', $customer_factory->get_last_name());
	}

	public function test_get_email() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_email());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('firstname@alma.com', $customer_factory->get_email());
	}

	public function test_get_billing_phone() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_phone());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('33000000000', $customer_factory->get_billing_phone());
	}

	public function test_get_billing_first_name() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_first_name());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingFirstName', $customer_factory->get_billing_first_name());
	}

	public function test_get_shipping_first_name() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_first_name());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingFirstName', $customer_factory->get_shipping_first_name());
	}

	public function test_get_billing_last_name() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_last_name());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingLastName', $customer_factory->get_billing_last_name());
	}

	public function test_get_shipping_last_name() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_last_name());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingLastName', $customer_factory->get_shipping_last_name());
	}

	public function test_get_shipping_address() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_address());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingAddress', $customer_factory->get_shipping_address());
	}

	public function test_get_billing_address() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_address());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingAddress', $customer_factory->get_billing_address());
	}

	public function test_get_shipping_address_2() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_address_2());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingAddress2', $customer_factory->get_shipping_address_2());
	}

	public function test_get_billing_address_2() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_address());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingAddress2', $customer_factory->get_billing_address_2());
	}

	public function test_get_billing_postcode() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_postcode());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingPostcode', $customer_factory->get_billing_postcode());
	}

	public function test_get_shipping_postcode() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_postcode());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingPostcode', $customer_factory->get_shipping_postcode());
	}

	public function test_get_billing_city() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_city());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingCity', $customer_factory->get_billing_city());
	}

	public function test_get_shipping_city() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_city());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingCity', $customer_factory->get_shipping_city());
	}


	public function test_get_billing_country() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_country());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('BillingCountry', $customer_factory->get_billing_country());
	}

	public function test_get_shipping_country() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_shipping_country());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('ShippingCountry', $customer_factory->get_shipping_country());
	}

	public function test_get_billing_email() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertNull( $customer_factory->get_billing_email());

		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);

		$this->assertEquals('billingemail@alma.com', $customer_factory->get_billing_email());
	}

	public function test_call_method() {
		$customer_factory = Mockery::mock( CustomerFactory::class )->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(null);

		$this->assertFalse( $customer_factory->call_method('get_first_name'));

		$php_factory = Mockery::mock( PHPFactory::class )->makePartial();
		$php_factory->shouldReceive('call_method', 'get_first_name')->andReturn('FirstName');

		$customer_factory = Mockery::mock( CustomerFactory::class , [$php_factory])->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn($this->customer);


		$this->assertEquals('FirstName',  $customer_factory->call_method('get_first_name'));

		$php_factory = Mockery::mock( PHPFactory::class )->makePartial();
		$php_factory->shouldReceive('call_method', 'get_first_name')->andReturn('FirstName');

		$customer_factory = Mockery::mock( CustomerFactory::class , [$php_factory])->makePartial();
		$customer_factory->shouldReceive('get_customer')->andReturn(false);

		$this->assertFalse( $customer_factory->call_method('get_great_first_name'));
	}
}



