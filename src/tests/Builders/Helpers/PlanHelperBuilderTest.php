<?php
/**
 * Class PlanHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Helpers\PlanHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Helpers;


use Alma\Woocommerce\Builders\Helpers\PlanHelperBuilder;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Helpers\PlanHelper;
use Alma\Woocommerce\Helpers\TemplateLoaderHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\PlanHelperBuilder
 */
class PlanHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The plan helper builder.
	 *
	 * @var PlanHelperBuilder $plan_helper_builder
	 */
	protected $plan_helper_builder;

	public function set_up() {
		$this->plan_helper_builder = new PlanHelperBuilder();
	}
	
	public function test_get_instance() {
		$this->assertInstanceOf(PlanHelper::class, $this->plan_helper_builder->get_instance());
	}

	public function test_get_gateway_helper() {
		$this->assertInstanceOf(GatewayHelper::class, $this->plan_helper_builder->get_gateway_helper());
		$this->assertInstanceOf(GatewayHelper::class, $this->plan_helper_builder->get_gateway_helper(
			\Mockery::mock(GatewayHelper::class)
		));
	}

	public function test_get_template_loader_helper() {
		$this->assertInstanceOf(TemplateLoaderHelper::class, $this->plan_helper_builder->get_template_loader_helper());
		$this->assertInstanceOf(TemplateLoaderHelper::class, $this->plan_helper_builder->get_template_loader_helper(new TemplateLoaderHelper()));
	}

}



