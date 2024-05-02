<?php
/**
 * Class CartHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\CartHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders;


use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Builders\CartHelperBuilder;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\CurrencyFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Factories\SessionFactory;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\CartHelperBuilder
 */
class CartHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The cart helper builder.
	 *
	 * @var CartHelperBuilder $cart_helper_builder
	 */
	protected $cart_helper_builder;
	public function set_up() {
		$this->cart_helper_builder = new CartHelperBuilder();
	}

	public function test_get_instance() {
		$this->assertInstanceOf(CartHelper::class, $this->cart_helper_builder->get_instance());
	}

	public function test_get_tools_helper() {
		$this->assertInstanceOf(ToolsHelper::class, $this->cart_helper_builder->get_tools_helper());
		$this->assertInstanceOf(ToolsHelper::class, $this->cart_helper_builder->get_tools_helper(
			new ToolsHelper(
				new AlmaLogger(),
				new PriceFactory(),
				new CurrencyFactory()
			)
		));
	}

	public function test_get_session_factory() {
		$this->assertInstanceOf(SessionFactory::class, $this->cart_helper_builder->get_session_factory());
		$this->assertInstanceOf(SessionFactory::class, $this->cart_helper_builder->get_session_factory(new SessionFactory()));
	}

	public function test_get_version_factory() {
		$this->assertInstanceOf(VersionFactory::class, $this->cart_helper_builder->get_version_factory());
		$this->assertInstanceOf(VersionFactory::class, $this->cart_helper_builder->get_version_factory(new VersionFactory()));
	}

	public function test_get_cart_factory() {
		$this->assertInstanceOf(CartFactory::class, $this->cart_helper_builder->get_cart_factory());
		$this->assertInstanceOf(CartFactory::class, $this->cart_helper_builder->get_cart_factory(new CartFactory()));
	}
}



