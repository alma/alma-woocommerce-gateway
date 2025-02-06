<?php
/**
 * Class GatewayHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Helpers\GatewayHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Helpers;


use Alma\Woocommerce\Builders\Helpers\GatewayHelperBuilder;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CheckoutHelper;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Helpers\PaymentHelper;
use Alma\Woocommerce\Helpers\PHPHelper;
use Alma\Woocommerce\Helpers\ProductHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\GatewayHelperBuilder
 */
class GatewayHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The tools helper builder.
	 *
	 * @var GatewayHelperBuilder $gateway_helper_builder
	 */
	protected $gateway_helper_builder;

	public function set_up() {
		$this->gateway_helper_builder = new GatewayHelperBuilder();
	}
	
	public function test_get_instance() {
		$this->assertInstanceOf(GatewayHelper::class, $this->gateway_helper_builder->get_instance());
	}

	public function test_get_payment_helpery() {
		$this->assertInstanceOf(PaymentHelper::class, $this->gateway_helper_builder->get_payment_helper());
		$this->assertInstanceOf(PaymentHelper::class, $this->gateway_helper_builder->get_payment_helper(new PaymentHelper()));
	}

	public function test_get_checkout_helper() {
		$this->assertInstanceOf(CheckoutHelper::class, $this->gateway_helper_builder->get_checkout_helper());
		$this->assertInstanceOf(CheckoutHelper::class, $this->gateway_helper_builder->get_checkout_helper(new CheckoutHelper()));
	}

	public function test_get_product_helper() {
		$this->assertInstanceOf(ProductHelper::class, $this->gateway_helper_builder->get_product_helper());
		$this->assertInstanceOf(ProductHelper::class, $this->gateway_helper_builder->get_product_helper( \Mockery::mock(ProductHelper::class)));
	}

	public function test_get_cart_helper() {
		$this->assertInstanceOf(CartHelper::class, $this->gateway_helper_builder->get_cart_helper());
		$this->assertInstanceOf(CartHelper::class, $this->gateway_helper_builder->get_cart_helper( \Mockery::mock(CartHelper::class)));
	}

	public function test_get_php_helper() {
		$this->assertInstanceOf(PHPHelper::class, $this->gateway_helper_builder->get_php_helper());
		$this->assertInstanceOf(PHPHelper::class, $this->gateway_helper_builder->get_php_helper(new PHPHelper()));
	}

}



