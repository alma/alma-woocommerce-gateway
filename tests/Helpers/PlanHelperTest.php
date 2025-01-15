<?php
/**
 * Class PlanHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\PlanHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\PlanHelperBuilder;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Helpers\PlanHelper;
use Alma\Woocommerce\Helpers\TemplateLoaderHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\PlanHelper
 */
class PlanHelperTest extends WP_UnitTestCase {

	/**
	 * @var PlanHelper
	 */
	protected $alma_plan_helper;

	/**
	 * @var \Mockery\MockInterface|PlanHelperBuilder
	 */
	protected $plan_builder_helper;

	/**
	 * @var \Mockery\MockInterface|AlmaSettings
	 */
	public $alma_settings_mock;


	/**
	 * @var \Mockery\MockInterface|GatewayHelper
	 */
	public $gateway_helper_mock;

	/**
	 * @var \Mockery\MockInterface|TemplateLoaderHelper
	 */
	public $template_loader_helper_mock;

	/**
	 * @var \Mockery\MockInterface|PriceFactory
	 */
	public $price_factory_mock;

	/**
	 * @var \Mockery\MockInterface|Eligibility
	 */
	public $eligibility_mock;
	public function set_up() {
		$this->alma_settings_mock = \Mockery::mock(AlmaSettings::class)->makePartial();
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID_PAY_LATER)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID_PAY_LATER)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID_PAY_NOW)->andReturn(true);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW)->andReturn(true);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::GATEWAY_ID_IN_PAGE)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID_IN_PAGE)->andReturn(true);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID_PAY_NOW)->andReturn(false);
		$this->alma_settings_mock->shouldReceive('should_display_plan')->with(ConstantsHelper::DEFAULT_FEE_PLAN, ConstantsHelper::GATEWAY_ID)->andReturn(true);

		$this->gateway_helper_mock = \Mockery::mock(GatewayHelper::class)->makePartial();
		$this->price_factory_mock = \Mockery::mock(PriceFactory::class)->makePartial();
		$this->eligibility_mock = \Mockery::mock(Eligibility::class);
		
		$this->template_loader_helper_mock = \Mockery::mock(TemplateLoaderHelper::class);
		$this->template_loader_helper_mock->shouldReceive('get_template')->andReturn('');
		
		$this->plan_builder_helper = \Mockery::mock(PlanHelperBuilder::class)->makePartial();
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$this->plan_builder_helper->shouldReceive('get_gateway_helper')->andReturn($this->gateway_helper_mock);
		$this->plan_builder_helper->shouldReceive('get_template_loader_helper')->andReturn($this->template_loader_helper_mock);
		$this->plan_builder_helper->shouldReceive('get_price_factory')->andReturn($this->price_factory_mock);
		
		$this->alma_plan_helper = $this->plan_builder_helper->get_instance();

	}

	public function test_get_plans_by_keys_empty_plans() {
		$this->assertEquals(array(), $this->alma_plan_helper->get_plans_by_keys());
	}

	public function test_get_plans_by_keys_empty_eligibilities() {
		$this->assertEquals(array(), $this->alma_plan_helper->get_plans_by_keys(
			 array(
				 ConstantsHelper::PAY_NOW_FEE_PLAN,
			 )
		));
	}

	public function test_get_plans_by_keys_empty_eligibilities_plans() {
		$this->assertEquals(
			array(),
			$this->alma_plan_helper->get_plans_by_keys(
				array(),
				array(
					ConstantsHelper::PAY_NOW_FEE_PLAN => $this->eligibility_mock ,
					ConstantsHelper::DEFAULT_FEE_PLAN => $this->eligibility_mock
				)
			));
	}

	public function test_get_plans_by_keys() {
		$this->assertEquals(
			array( ConstantsHelper::PAY_NOW_FEE_PLAN => $this->eligibility_mock ),
			$this->alma_plan_helper->get_plans_by_keys(
			array(
					ConstantsHelper::PAY_NOW_FEE_PLAN,
			),
			array(
				ConstantsHelper::PAY_NOW_FEE_PLAN => $this->eligibility_mock ,
				ConstantsHelper::DEFAULT_FEE_PLAN => $this->eligibility_mock
			)
		));
	}

	public function test_order_plans_empty_array() {
		$this->assertEquals(array(), $this->alma_plan_helper->order_plans());
	}
	public function test_order_plans_empty_plans() {
		$this->assertEquals(array(), $this->alma_plan_helper->order_plans(array(), ConstantsHelper::GATEWAY_ID));
	}

	public function test_order_plans_empty_gateway_id() {
		$alma_settings_mock = \Mockery::mock(AlmaSettings::class)->makePartial();
		$alma_settings_mock->settings['display_in_page'] = 'no';
		$alma_settings_mock->shouldReceive('should_display_plan')->andReturn(false);

		$plan_builder_helper = \Mockery::mock(PlanHelperBuilder::class)->makePartial();
		$plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($alma_settings_mock);
		$plan_helper = $plan_builder_helper->get_instance();
		$this->assertEquals(array(), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN)));
	}

	public function test_order_plans_no_gateway_id_no_in_page_display_plan_ok() {
		$this->alma_settings_mock->settings['display_in_page'] = 'no';
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$plan_helper = $this->plan_builder_helper->get_instance();

		$this->assertEquals(array(
			ConstantsHelper::GATEWAY_ID_PAY_NOW        => array(ConstantsHelper::PAY_NOW_FEE_PLAN),
			ConstantsHelper::GATEWAY_ID                => array(ConstantsHelper::DEFAULT_FEE_PLAN),
		), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::DEFAULT_FEE_PLAN)));
	}

	public function test_order_plans_no_gateway_id_with_in_page_display_plan_ok() {
		$this->alma_settings_mock->settings['display_in_page'] = 'yes';
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$plan_helper = $this->plan_builder_helper->get_instance();

		$this->assertEquals(array(
			ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW        => array(ConstantsHelper::PAY_NOW_FEE_PLAN),
			ConstantsHelper::GATEWAY_ID_IN_PAGE                => array(ConstantsHelper::DEFAULT_FEE_PLAN),
		), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::DEFAULT_FEE_PLAN)));
	}

	public function test_order_plans_gateway_id_exclude_with_in_page_display_plan_ok() {
		$this->alma_settings_mock->settings['display_in_page'] = 'yes';
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$plan_helper = $this->plan_builder_helper->get_instance();

		$this->assertEquals(array(), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::DEFAULT_FEE_PLAN), ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER));
	}

	public function test_order_plans_gateway_id_exclude_no_in_page_display_plan_ok() {
		$this->alma_settings_mock->settings['display_in_page'] = 'no';
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$plan_helper = $this->plan_builder_helper->get_instance();

		$this->assertEquals(array( ), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::DEFAULT_FEE_PLAN),ConstantsHelper::GATEWAY_ID_PAY_LATER));
	}

	public function test_order_plans_gateway_id_with_in_page_display_plan_ok() {
		$this->alma_settings_mock->settings['display_in_page'] = 'yes';
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$plan_helper = $this->plan_builder_helper->get_instance();

		$this->assertEquals(array(ConstantsHelper::PAY_NOW_FEE_PLAN), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::DEFAULT_FEE_PLAN), ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW));
	}

	public function test_order_plans_gateway_id_no_in_page_display_plan_ok() {
		$this->alma_settings_mock->settings['display_in_page'] = 'no';
		$this->plan_builder_helper->shouldReceive('get_alma_settings')->andReturn($this->alma_settings_mock);
		$plan_helper = $this->plan_builder_helper->get_instance();

		$this->assertEquals(array(ConstantsHelper::DEFAULT_FEE_PLAN ), $plan_helper->order_plans(array(ConstantsHelper::PAY_NOW_FEE_PLAN, ConstantsHelper::DEFAULT_FEE_PLAN),ConstantsHelper::GATEWAY_ID));
	}

	public function test_render_field_classic_no_default_plan() {
		$this->assertNull($this->alma_plan_helper->render_fields_classic(
			array( ConstantsHelper::PAY_NOW_FEE_PLAN ),
			array( ConstantsHelper::GATEWAY_ID_PAY_NOW => array( ConstantsHelper::PAY_NOW_FEE_PLAN) ),
			ConstantsHelper::GATEWAY_ID_PAY_NOW
		));
	}
	public function test_render_field_classic_default_plan() {
		$this->assertNull($this->alma_plan_helper->render_fields_classic(
			array( ConstantsHelper::PAY_NOW_FEE_PLAN ),
			array( ConstantsHelper::GATEWAY_ID_PAY_NOW => array( ConstantsHelper::PAY_NOW_FEE_PLAN) ),
			ConstantsHelper::GATEWAY_ID_PAY_NOW,
			ConstantsHelper::DEFAULT_FEE_PLAN
		));
	}

	public function test_render_field_in_page_no_default_plan() {
		$this->expectOutputString('<div id="alma-inpage-alma_in_page_pay_now"></div></div>');
		$this->alma_plan_helper->render_fields_in_page(
			array( ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW => array( ConstantsHelper::PAY_NOW_FEE_PLAN ) ),
			ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW
		) ;
	}

	public function test_render_field_in_page_default_plan() {
		$this->expectOutputString('<div id="alma-inpage-alma_in_page_pay_now"></div></div>');
		$this->alma_plan_helper->render_fields_in_page(
			array( ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW => array( ConstantsHelper::PAY_NOW_FEE_PLAN ) ),
			ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW,
			ConstantsHelper::DEFAULT_FEE_PLAN
		) ;
	}

	public function test_render_checkout_fields_no_plan_eligible() {
		$this->assertNull($this->alma_plan_helper->render_checkout_fields(
			array(),
			array(),
			ConstantsHelper::GATEWAY_ID_PAY_NOW
		));
	}

	public function test_render_checkout_fields_no_plan_eligible_no_gateway_in_plans() {

		$this->assertNull($this->alma_plan_helper->render_checkout_fields(
			array(),
			array( ConstantsHelper::GATEWAY_ID_PAY_LATER=> array() ),
			ConstantsHelper::GATEWAY_ID_PAY_NOW
		));
	}

	/**
	 * @dataProvider checkout_fields_provider
	 */
	public function test_render_checkout_fields_in_page($gateway_id, $shoud_received, $should_not_received) {

		$this->gateway_helper_mock->shouldReceive( 'get_alma_gateway_title' )->andReturn( 'test' );
		$this->gateway_helper_mock->shouldReceive( 'get_alma_gateway_description' )->andReturn( 'test' );

		$plan_helper = \Mockery::spy( PlanHelper::class, [
			$this->alma_settings_mock,
			$this->gateway_helper_mock,
			$this->template_loader_helper_mock,
			$this->price_factory_mock
		] )->makePartial();
		$plan_helper->shouldReceive( $shoud_received )->andReturn( null );

		$this->assertNull( $plan_helper->render_checkout_fields(
			array(),
			array( $gateway_id => array( 'test' ) ),
			$gateway_id
		) );
		$plan_helper->shouldHaveReceived( $shoud_received );
		$plan_helper->shouldNotHaveReceived( $should_not_received );
	}

	public function checkout_fields_provider() {

		return array(
			'gateway_id_in_page' => array(
				ConstantsHelper::GATEWAY_ID_IN_PAGE,
				'render_fields_in_page',
				'render_fields_classic'
			),
			'gateway_id_in_page_pay_now' => array(
				ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW,
				'render_fields_in_page',
				'render_fields_classic'
			),
			'gateway_id_in_page_pay_later' => array(
				ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER,
				'render_fields_in_page',
				'render_fields_classic'
			)
		);
	}
	public function test_render_checkout_fields() {

		$this->gateway_helper_mock->shouldReceive('get_alma_gateway_title')->andReturn('test');
		$this->gateway_helper_mock->shouldReceive('get_alma_gateway_description')->andReturn('test');

		$plan_helper = \Mockery::spy(PlanHelper::class, [
			$this->alma_settings_mock,
			$this->gateway_helper_mock,
			$this->template_loader_helper_mock,
			$this->price_factory_mock
		])->makePartial();

		$plan_helper->shouldReceive( 'render_fields_classic' )->andReturn(null);

		$this->assertNull($plan_helper->render_checkout_fields(
			array(),
			array( ConstantsHelper::GATEWAY_ID => array('test') ),
			ConstantsHelper::GATEWAY_ID
		));

		$plan_helper->shouldHaveReceived('render_fields_classic');
		$plan_helper->shouldNotHaveReceived('render_fields_in_page');

	}


	public function tear_down() {
		$this->alma_plan_helper = null;
		$this->plan_builder_helper = null;
		$this->alma_settings_mock =null;
		$this->gateway_helper_mock =null;
		$this->template_loader_helper_mock =null;
		$this->price_factory_mock =null;
		\Mockery::close();
	}
}
