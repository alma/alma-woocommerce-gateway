<?php
/**
 * Class ProductHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\ProductHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Factories\CartFactory;;
use Alma\Woocommerce\Factories\CoreFactory;
use Alma\Woocommerce\Helpers\ProductHelper;
use Mockery;
use WP_UnitTestCase;
/**
 * @covers \Alma\Woocommerce\Helpers\ProductHelper
 */
class ProductHelperTest extends WP_UnitTestCase {

	/**
	 * @var AlmaLogger|Mockery\Mock
	 */
	protected $alma_logger;

	/**
	 * @var AlmaSettings|Mockery\Mock
	 */
	protected $alma_settings;

	/**
	 * @var CoreFactory|Mockery\Mock
	 */
	protected $core_factory;

	/**
	 * @var CartFactory|Mockery\Mock
	 */
	protected $cart_factory;
	public function set_up() {
		$this->alma_logger = Mockery::mock(AlmaLogger::class);
		$this->alma_settings = Mockery::mock(AlmaSettings::class);
		$this->core_factory = Mockery::mock(CoreFactory::class);
		$this->cart_factory = Mockery::mock(CartFactory::class);
	}
	public function test_cart_has_excluded_product() {

		// test get cart to null
		$cart_factory = $this->cart_factory->makePartial();
		$cart_factory->shouldReceive('get_cart')->andReturn( null);

		$product_helper = \Mockery::mock(
			ProductHelper::class,
			[
				$this->alma_logger,
				$this->alma_settings,
				$cart_factory,
				$this->core_factory
			])->makePartial();

		$this->assertFalse($product_helper->cart_has_excluded_product());

		// test has excluded categories
		$cart_factory = $this->cart_factory->makePartial();
		$cart_factory->shouldReceive('get_cart')->andReturn( new \WC_Cart());

		$product_helper = \Mockery::mock(
			ProductHelper::class,
			[
				$this->alma_logger,
				$this->alma_settings,
				$cart_factory,
				$this->core_factory
			])->makePartial();
		$product_helper->shouldReceive('has_excluded_categories')->andReturn(false);

		$this->assertFalse($product_helper->cart_has_excluded_product());

		// Test has cart items and product excluded
		$cart_factory = Mockery::mock(CartFactory::class)->makePartial();
		$cart_factory->shouldReceive('get_cart')->andReturn( new \WC_Cart());
		$cart_factory->shouldReceive('get_cart_items')->andReturn( array(
			array(
				'product_id' => 1
			)
		));

		$product_helper = \Mockery::mock(
			ProductHelper::class,
			[
				$this->alma_logger,
				$this->alma_settings,
				$cart_factory,
				$this->core_factory
			])->makePartial();
		$product_helper->shouldReceive('has_excluded_categories')->andReturn(true);
		$product_helper->shouldReceive('is_product_excluded')->andReturn(true);

		$this->assertTrue($product_helper->cart_has_excluded_product());


		// Test has cart items and no product excluded
		$product_helper = \Mockery::mock(
			ProductHelper::class,
			[
				$this->alma_logger,
				$this->alma_settings,
				$cart_factory,
				$this->core_factory
			])->makePartial();
		$product_helper->shouldReceive('has_excluded_categories')->andReturn(true);
		$product_helper->shouldReceive('is_product_excluded')->andReturn(false);

		$this->assertFalse($product_helper->cart_has_excluded_product());

	}

	public function test_has_excluded_categories()
	{
		$alma_settings = $this->alma_settings->makePartial();
		$alma_settings->excluded_products_list = null;

		$product_helper = \Mockery::mock(ProductHelper::class, [
			$this->alma_logger,
			$alma_settings,
			$this->cart_factory,
			$this->core_factory
		])->makePartial();

		$this->assertFalse($product_helper->has_excluded_categories());

		$alma_settings = $this->alma_settings->makePartial();
		$alma_settings->excluded_products_list = array(
			'maCategorie1'
		);

		$product_helper = \Mockery::mock(ProductHelper::class, [
			$this->alma_logger,
			$alma_settings,
			$this->cart_factory,
			$this->core_factory
		])->makePartial();

		$this->assertTrue($product_helper->has_excluded_categories());
	}

	public function test_is_product_excluded() {
		// Test no excluded product list.
		$alma_settings = $this->alma_settings->makePartial();
		$alma_settings->excluded_products_list = array();

		$product_helper = \Mockery::mock(ProductHelper::class, [
			$this->alma_logger,
			$alma_settings,
			$this->cart_factory,
			$this->core_factory
		])->makePartial();

		$this->assertFalse($product_helper->is_product_excluded(1));

		// Test excluded product list but no term.
		$alma_settings = $this->alma_settings->makePartial();
		$alma_settings->excluded_products_list = array(
			'maCategorie1'
		);

		$this->core_factory->makePartial();
		$this->core_factory->shouldReceive('has_term')->andReturn(false);

		$product_helper = \Mockery::mock(ProductHelper::class, [
			$this->alma_logger,
			$alma_settings,
			$this->cart_factory,
			$this->core_factory
		])->makePartial();

		$this->assertFalse($product_helper->is_product_excluded(1));

		// Test excluded product list and term exists.
		$alma_settings = $this->alma_settings->makePartial();
		$alma_settings->excluded_products_list = array(
			'maCategorie1'
		);

		$coreFactory = Mockery::mock(CoreFactory::class)->makePartial();
		$coreFactory->shouldReceive('has_term')->andReturn(true);

		$product_helper = \Mockery::mock(ProductHelper::class, [
			$this->alma_logger,
			$alma_settings,
			$this->cart_factory,
			$coreFactory
		])->makePartial();

		$this->assertTrue($product_helper->is_product_excluded(1));
	}
}



