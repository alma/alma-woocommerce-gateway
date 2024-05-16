<?php
/**
 * Class ToolsHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Helpers;


use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Factories\CurrencyFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Helpers\ToolsHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder
 */
class ToolsHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The tools helper builder.
	 *
	 * @var ToolsHelperBuilder $tools_helper_builder
	 */
	protected $tools_helper_builder;

	public function set_up() {
		$this->tools_helper_builder = new ToolsHelperBuilder();
	}
	
	public function test_get_instance() {
		$this->assertInstanceOf(ToolsHelper::class, $this->tools_helper_builder->get_instance());
	}

	public function test_get_price_factory() {
		$this->assertInstanceOf(PriceFactory::class, $this->tools_helper_builder->get_price_factory());
		$this->assertInstanceOf(PriceFactory::class, $this->tools_helper_builder->get_price_factory(new PriceFactory()));
	}

	public function test_get_alma_logger() {
		$this->assertInstanceOf(AlmaLogger::class, $this->tools_helper_builder->get_alma_logger());
		$this->assertInstanceOf(AlmaLogger::class, $this->tools_helper_builder->get_alma_logger(new AlmaLogger()));
	}

	public function test_get_currency_factory() {
		$this->assertInstanceOf(CurrencyFactory::class, $this->tools_helper_builder->get_currency_factory());
		$this->assertInstanceOf(CurrencyFactory::class, $this->tools_helper_builder->get_currency_factory( new CurrencyFactory()));
	}

}



