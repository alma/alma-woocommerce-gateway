<?php
/**
 * Class ProductHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Helpers\ProductHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Helpers;

use Alma\Woocommerce\Builders\Helpers\ProductHelperBuilder;
use Alma\Woocommerce\Factories\CoreFactory;
use Alma\Woocommerce\Helpers\ProductHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\ProductHelperBuilder
 */
class ProductHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The product helper builder.
	 *
	 * @var ProductHelperBuilder $product_helper_builder
	 */
	protected $product_helper_builder;
	public function set_up() {
		$this->product_helper_builder = new ProductHelperBuilder();
	}

	public function test_get_instance() {
		$this->assertInstanceOf(ProductHelper::class, $this->product_helper_builder->get_instance());
	}

	public function test_get_core_factory() {
		$this->assertInstanceOf(CoreFactory::class, $this->product_helper_builder->get_core_factory());
		$this->assertInstanceOf(CoreFactory::class, $this->product_helper_builder->get_core_factory(new CoreFactory()));
	}

}



