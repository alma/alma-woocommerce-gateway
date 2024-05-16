<?php
/**
 * Class CustomerHelper
 *
 * @covers \Alma\Woocommerce\Helpers\CartHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;
use Alma\Woocommerce\Factories\CustomerFactory;
use Alma\Woocommerce\Factories\PHPFactory;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CustomerHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\CustomerHelper
 */
class CustomerHelperTest extends WP_UnitTestCase {

	/**
	 * CustomerHelper.
	 *
	 * @var CustomerHelper $customer_helper
	 */
	protected $customer_helper;

	/**
	 * \WC_Customer .
	 *
	 * @var \WC_Customer $customer
	 */
	protected $customer;


	/**
	 * Result  .
	 *
	 * @var array $result_data
	 */
	protected $result_data;

	public function set_up() {

		$this->customer = new \WC_Customer();
		$this->customer->set_first_name('FirstName');
		$this->customer->set_last_name('LastName');
		$this->customer->set_email('firstname@alma.com');
		$this->customer->set_billing_phone('+33000000000');
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

		$customer_factory = \Mockery::mock(CustomerFactory::class)->makePartial();
		$customer_factory->shouldReceive('get_first_name')->andReturn($this->customer->get_first_name());
		$customer_factory->shouldReceive('get_last_name')->andReturn($this->customer->get_last_name());
		$customer_factory->shouldReceive('get_email')->andReturn($this->customer->get_email());
		$customer_factory->shouldReceive('get_billing_phone')->andReturn($this->customer->get_billing_phone());
		$customer_factory->shouldReceive('get_billing_first_name')->andReturn($this->customer->get_billing_first_name());
		$customer_factory->shouldReceive('get_billing_last_name')->andReturn($this->customer->get_billing_last_name());
		$customer_factory->shouldReceive('get_billing_address')->andReturn($this->customer->get_billing_address());
		$customer_factory->shouldReceive('get_billing_address_2')->andReturn($this->customer->get_billing_address_2());
		$customer_factory->shouldReceive('get_billing_postcode')->andReturn($this->customer->get_billing_postcode());
		$customer_factory->shouldReceive('get_billing_city')->andReturn($this->customer->get_billing_city());
		$customer_factory->shouldReceive('get_billing_country')->andReturn($this->customer->get_billing_country());
		$customer_factory->shouldReceive('get_billing_email')->andReturn($this->customer->get_billing_email());
		$customer_factory->shouldReceive('get_shipping_first_name')->andReturn($this->customer->get_shipping_first_name());
		$customer_factory->shouldReceive('get_shipping_last_name')->andReturn($this->customer->get_shipping_last_name());
		$customer_factory->shouldReceive('get_shipping_address')->andReturn($this->customer->get_shipping_address());
		$customer_factory->shouldReceive('get_shipping_address_2')->andReturn($this->customer->get_shipping_address_2());
		$customer_factory->shouldReceive('get_shipping_postcode')->andReturn($this->customer->get_shipping_postcode());
		$customer_factory->shouldReceive('get_shipping_city')->andReturn($this->customer->get_shipping_city());
		$customer_factory->shouldReceive('get_shipping_country')->andReturn($this->customer->get_shipping_country());

		$this->customer_helper = \Mockery::mock(CustomerHelper::class, [$customer_factory])->makePartial();

		$this->result_data = array(
			'first_name' => $this->customer->get_first_name(),
			'last_name' => $this->customer->get_last_name(),
			'email' => $this->customer->get_email(),
			'phone' => $this->customer->get_billing_phone(),
			'addresses' => array(
				array(
					'first_name' => $this->customer->get_billing_first_name(),
					'last_name' => $this->customer->get_billing_last_name(),
					'line1' => $this->customer->get_billing_address(),
					'line2' => $this->customer->get_billing_address_2(),
					'postal_code' => $this->customer->get_billing_postcode(),
					'city' => $this->customer->get_billing_city(),
					'country' => $this->customer->get_billing_country(),
					'email' => $this->customer->get_billing_email(),
					'phone' => $this->customer->get_billing_phone(),
				),
				array(
					'first_name' => $this->customer->get_shipping_first_name(),
					'last_name' => $this->customer->get_shipping_last_name(),
					'line1' => $this->customer->get_shipping_address(),
					'line2' => $this->customer->get_shipping_address_2(),
					'postal_code' => $this->customer->get_shipping_postcode(),
					'city' => $this->customer->get_shipping_city(),
					'country' => $this->customer->get_shipping_country(),
				)
			)
		);
	}

	public function test_get_data() {
		$this->assertEquals($this->result_data, $this->customer_helper->get_data());
	}

	public function test_get_customer_data() {
		$data = array(
			'maclé' => 'mavaleur'
		);

		$customer_factory = \Mockery::mock(CustomerFactory::class)->makePartial();
		$customer_factory->shouldReceive('call_method')->andReturn('LastName');

		$customer_helper = \Mockery::mock(CustomerHelper::class, [$customer_factory])->makePartial();

		$this->assertEquals(array(
			'maclé' => 'mavaleur',
			'last_name' => 'LastName'
		), $customer_helper->get_customer_data('get_last_name', $data, 'last_name'));

		$customer_factory = \Mockery::mock(CustomerFactory::class)->makePartial();
		$customer_factory->shouldReceive('call_method')->andReturn(false);

		$customer_helper = \Mockery::mock(CustomerHelper::class, [$customer_factory])->makePartial();

		$this->assertEquals(array(
			'maclé' => 'mavaleur',
		), $customer_helper->get_customer_data('get_last_name', $data, 'last_name'));

		$customer_factory = \Mockery::mock(CustomerFactory::class)->makePartial();
		$customer_factory->shouldReceive('call_method')->andReturn('LastName');

		$customer_helper = \Mockery::mock(CustomerHelper::class, [$customer_factory])->makePartial();

		$data = array(
			'maclé' => 'mavaleur',
			'last_name' => 'NoLastName'
		);

		$this->assertEquals(array(
			'maclé' => 'mavaleur',
			'last_name' => 'NoLastName'
		), $customer_helper->get_customer_data('get_last_name', $data, 'last_name'));
	}

	public function test_get_billing_address() {
		$this->assertEquals($this->result_data['addresses'][0], $this->customer_helper->get_billing_address());
	}

	public function test_get_shipping_address() {
		$this->assertEquals($this->result_data['addresses'][1], $this->customer_helper->get_shipping_address());
	}
}



