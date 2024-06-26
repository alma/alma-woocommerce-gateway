<?php
/**
 * Class GatewayHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\GatewayHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;


use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\GatewayHelperBuilder;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\CoreFactory;
use Alma\Woocommerce\Gateways\Standard\StandardGateway;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Helpers\PHPHelper;
use Alma\Woocommerce\Helpers\ProductHelper;
use project\Controller\TodoController;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\GatewayHelper
 */
class GatewayHelperTest extends WP_UnitTestCase {

	/**
	 * @var GatewayHelper
	 */
	protected $gateway_helper;

	/**
	 * @var \Mockery\MockInterface|GatewayHelperBuilder

	 */
	protected $gateway_helper_builder;

	/**
	 * @var \Mockery\MockInterface|CoreFactory

	 */
	protected $core_factory;


	/**
	 * @var \Mockery\MockInterface|ProductHelper

	 */
	protected $product_helper;

	/**
	 * @var \Mockery\MockInterface|CartHelper

	 */
	protected $cart_helper;

	/**
	 * @var \Mockery\MockInterface|CartFactory

	 */
	protected $cart_factory;

	/**
	 * @var \Mockery\MockInterface|AlmaSettings

	 */
	protected $alma_settings;

	/**
	 * @var \Mockery\MockInterface|PHPHelper

	 */
	protected $php_helper;



	public function set_up() {
		$this->gateway_helper_builder = \Mockery::mock( GatewayHelperBuilder::class )->makePartial();
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->core_factory = \Mockery::mock( CoreFactory::class )->makePartial();
		$this->product_helper = \Mockery::mock( ProductHelper::class )->makePartial();
		$this->cart_helper = \Mockery::mock( CartHelper::class )->makePartial();
		$this->cart_factory = \Mockery::mock( CartFactory::class )->makePartial();
		$this->alma_settings = \Mockery::mock( AlmaSettings::class )->makePartial();
		$this->php_helper = \Mockery::mock( PHPHelper::class )->makePartial();
	}

	public function tear_down() {
		$this->gateway_helper = null;
		$this->gateway_helper_builder = null;
		$this->core_factory = null;
		$this->product_helper = null;
		$this->alma_settings = null;
		$this->cart_helper = null;
		$this->cart_factory = null;
		$this->php_helper = null;
		\Mockery::close();
	}

	public function test_woocommerce_available_payment_gateways_is_admin() {
		$this->core_factory->shouldReceive( 'is_admin' )->andReturn( true );

		$this->gateway_helper_builder->shouldReceive('get_core_factory' )->andReturn( $this->core_factory );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$result = array(
			ConstantsHelper::GATEWAY_ID,
			ConstantsHelper::GATEWAY_ID_PAY_LATER
		);

		$this->assertEquals($result, $this->gateway_helper->woocommerce_available_payment_gateways($result));
	}

	public function test_woocommerce_available_payment_gateways_no_product_excluded() {
		$this->core_factory->shouldReceive( 'is_admin' )->andReturn( false );
		$this->product_helper->shouldReceive( 'cart_has_excluded_product' )->andReturn( false);

		$this->gateway_helper_builder->shouldReceive('get_core_factory' )->andReturn( $this->core_factory );
		$this->gateway_helper_builder->shouldReceive('get_product_helper' )->andReturn( $this->product_helper );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$gateway_alma = \Mockery::mock(StandardGateway::class)->makePartial();
		$gateway_alma->id = ConstantsHelper::GATEWAY_ID;

		$result = array(
			ConstantsHelper::GATEWAY_ID => $gateway_alma
		);

		$this->assertEquals($result, $this->gateway_helper->woocommerce_available_payment_gateways($result));
	}

	public function test_woocommerce_available_payment_gateways_with_product_excluded() {
		$this->core_factory->shouldReceive( 'is_admin' )->andReturn( false );
		$this->product_helper->shouldReceive( 'cart_has_excluded_product' )->andReturn( true);

		$this->gateway_helper_builder->shouldReceive('get_core_factory' )->andReturn( $this->core_factory );
		$this->gateway_helper_builder->shouldReceive('get_product_helper' )->andReturn( $this->product_helper );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$gateway_alma = \Mockery::mock(StandardGateway::class)->makePartial();
		$gateway_alma->id = ConstantsHelper::GATEWAY_ID;

		$result = array(
			ConstantsHelper::GATEWAY_ID => $gateway_alma
		);

		$this->assertEquals(array(), $this->gateway_helper->woocommerce_available_payment_gateways($result));
	}

	public function test_woocommerce_gateway_title_with_settings() {
		$this->alma_settings->shouldReceive( 'get_title' )->with(ConstantsHelper::GATEWAY_ID)->andReturn( 'My title' );

		$this->gateway_helper_builder->shouldReceive('get_alma_settings' )->andReturn( $this->alma_settings );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('My title', $this->gateway_helper->woocommerce_gateway_title('Default title', ConstantsHelper::GATEWAY_ID));
	}

	public function test_woocommerce_gateway_title_without_settings() {
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('Default title', $this->gateway_helper->woocommerce_gateway_title('Default title', 'gateway_id'));
	}

	public function test_woocommerce_gateway_description_with_settings() {
		$this->alma_settings->shouldReceive( 'get_description' )->with(ConstantsHelper::GATEWAY_ID)->andReturn( 'My description' );

		$this->gateway_helper_builder->shouldReceive('get_alma_settings' )->andReturn( $this->alma_settings );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('My description', $this->gateway_helper->woocommerce_gateway_description('Default title', ConstantsHelper::GATEWAY_ID));
	}

	public function test_woocommerce_gateway_description_without_settings() {
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('Default description', $this->gateway_helper->woocommerce_gateway_description('Default description', 'gateway_id'));
	}

	public function test_woocommerce_alma_gateway_title_with_settings() {
		$this->alma_settings->shouldReceive( 'get_title' )->with(ConstantsHelper::GATEWAY_ID, true)->andReturn( 'My title Block' );

		$this->gateway_helper_builder->shouldReceive('get_alma_settings' )->andReturn( $this->alma_settings );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('My title Block', $this->gateway_helper->get_alma_gateway_title(ConstantsHelper::GATEWAY_ID, true));
	}

	public function test_woocommerce_alma_gateway_title_with_settings_and_no_blocks() {
		$this->alma_settings->shouldReceive( 'get_title' )->with(ConstantsHelper::GATEWAY_ID, false)->andReturn( 'My title Block' );

		$this->gateway_helper_builder->shouldReceive('get_alma_settings' )->andReturn( $this->alma_settings );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('My title Block', $this->gateway_helper->get_alma_gateway_title(ConstantsHelper::GATEWAY_ID, false));
	}

	public function test_woocommerce_alma_gateway_title_with_wrong_gateway_id() {

		$this->expectException(AlmaException::class);
		$this->expectExceptionMessage('Unknown gateway id : fake_id');
		$this->gateway_helper->get_alma_gateway_title('fake_id', true);
	}

	public function test_get_alma_logo_text_wrong_id() {
		$this->assertEquals('null', $this->gateway_helper->get_alma_gateway_logo_text(ConstantsHelper::GATEWAY_ID_PAY_LATER));
	}

	public function test_get_alma_logo_text() {
		$this->assertEquals('Pay Now', $this->gateway_helper->get_alma_gateway_logo_text(ConstantsHelper::GATEWAY_ID_PAY_NOW));
		$this->assertEquals('Pay Now', $this->gateway_helper->get_alma_gateway_logo_text(ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW));
	}

	public function test_is_in_page_gateway_not_an_inpage() {
		$this->assertFalse($this->gateway_helper->is_in_page_gateway(ConstantsHelper::GATEWAY_ID_PAY_LATER));
	}

	public function test_is_in_page_gateway() {
		$this->assertTrue($this->gateway_helper->is_in_page_gateway(ConstantsHelper::GATEWAY_ID_IN_PAGE));
	}

	public function test_woocommerce_alma_gateway_description_with_settings() {
		$this->alma_settings->shouldReceive( 'get_description' )->with(ConstantsHelper::GATEWAY_ID, true)->andReturn( 'My description Block' );

		$this->gateway_helper_builder->shouldReceive('get_alma_settings' )->andReturn( $this->alma_settings );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('My description Block', $this->gateway_helper->get_alma_gateway_description(ConstantsHelper::GATEWAY_ID, true));
	}

	public function test_woocommerce_alma_gateway_description_with_settings_and_no_blocks() {
		$this->alma_settings->shouldReceive( 'get_description' )->with(ConstantsHelper::GATEWAY_ID, false)->andReturn( 'My description Block' );

		$this->gateway_helper_builder->shouldReceive('get_alma_settings' )->andReturn( $this->alma_settings );
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();


		$this->assertEquals('My description Block', $this->gateway_helper->get_alma_gateway_description(ConstantsHelper::GATEWAY_ID, false));
	}

	public function test_woocommerce_alma_gateway_description_with_wrong_gateway_id() {

		$this->expectException(AlmaException::class);
		$this->expectExceptionMessage('Unknown gateway id : fake_id');
		$this->gateway_helper->get_alma_gateway_description('fake_id', true);
	}

	public function test_is_there_eligibility_in_cart_no_eligibility_found() {
		$this->cart_helper->shouldReceive( 'get_eligible_plans_keys_for_cart' )->andReturn( array());
		$this->gateway_helper_builder->shouldReceive('get_cart_helper' )->andReturn( $this->cart_helper );

		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->assertFalse($this->gateway_helper->is_there_eligibility_in_cart());
	}

	public function test_get_default_plans_no_plans() {
		$this->assertNull($this->gateway_helper->get_default_plan(array()));
	}

	public function test_get_default_plans_default_plan_found() {
		$this->assertEquals(
			ConstantsHelper::DEFAULT_FEE_PLAN,
			$this->gateway_helper->get_default_plan(
				array(ConstantsHelper::DEFAULT_FEE_PLAN,
					ConstantsHelper::PAY_NOW_FEE_PLAN
				)
			)
		);
	}

	public function test_get_default_plans_default_plan_array_found() {
		$this->assertEquals(
			ConstantsHelper::DEFAULT_FEE_PLAN,
			$this->gateway_helper->get_default_plan(
				array(
					array(ConstantsHelper::DEFAULT_FEE_PLAN ),
					array(ConstantsHelper::PAY_NOW_FEE_PLAN)
				)
			)
		);
	}

	public function test_get_default_plans_default_plan() {
		$this->assertEquals(
			ConstantsHelper::DEFAULT_FEE_PLAN,
			$this->gateway_helper->get_default_plan(
				array(
					ConstantsHelper::DEFAULT_FEE_PLAN ,
					ConstantsHelper::PAY_NOW_FEE_PLAN
				)
			)
		);
	}

	public function test_cart_contains_excluded_category_empty_cart()
	{
		$this->cart_factory->shouldReceive('get_cart')->andReturn(null);
		$this->gateway_helper_builder->shouldReceive('get_cart_factory')->andReturn($this->cart_factory);
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->assertFalse($this->gateway_helper->cart_contains_excluded_category());
	}

	public function test_cart_contains_excluded_category_setting_property_not_exists()
	{
		$this->cart_factory->shouldReceive('get_cart')->andReturn(\Mockery::mock(\WC_Cart::class));
		$this->php_helper->shouldReceive('property_exists')->andReturn(false);
		$this->gateway_helper_builder->shouldReceive('get_php_helper')->andReturn($this->php_helper);
		$this->gateway_helper_builder->shouldReceive('get_cart_factory')->andReturn($this->cart_factory);
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->assertFalse($this->gateway_helper->cart_contains_excluded_category());
	}

	public function test_cart_contains_excluded_category_setting_property_not_an_array()
	{
		$this->cart_factory->shouldReceive('get_cart')->andReturn(\Mockery::mock(\WC_Cart::class));
		$this->alma_settings->excluded_products_list = 'not_an_array';
		$this->php_helper->shouldReceive('property_exists')->andReturn(true);
		$this->gateway_helper_builder->shouldReceive('get_php_helper')->andReturn($this->php_helper);
		$this->gateway_helper_builder->shouldReceive('get_cart_factory')->andReturn($this->cart_factory);
		$this->gateway_helper_builder->shouldReceive('get_alma_settings')->andReturn($this->alma_settings);
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->assertFalse($this->gateway_helper->cart_contains_excluded_category());
	}

	public function test_cart_contains_excluded_category_setting_property_no_exclusion()
	{
		$this->cart_factory->shouldReceive('get_cart')->andReturn(\Mockery::mock(\WC_Cart::class));
		$cart_item = array('product_id' => 1);

		$this->cart_factory->shouldReceive('get_cart_items')->andReturn(array($cart_item));

		$this->alma_settings->excluded_products_list = array( 'slug2' );

		$this->core_factory->shouldReceive('has_term')->andReturn(false);
		$this->php_helper->shouldReceive('property_exists')->andReturn(true);
		$this->gateway_helper_builder->shouldReceive('get_php_helper')->andReturn($this->php_helper);
		$this->gateway_helper_builder->shouldReceive('get_cart_factory')->andReturn($this->cart_factory);
		$this->gateway_helper_builder->shouldReceive('get_alma_settings')->andReturn($this->alma_settings);
		$this->gateway_helper_builder->shouldReceive('get_core_factory')->andReturn($this->core_factory);
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->assertFalse($this->gateway_helper->cart_contains_excluded_category());
	}

	public function test_cart_contains_excluded_category_setting_property_with_exclusion()
	{
		$this->cart_factory->shouldReceive('get_cart')->andReturn(\Mockery::mock(\WC_Cart::class));
		$cart_item = array('product_id' => '1');

		$this->cart_factory->shouldReceive('get_cart_items')->andReturn(array($cart_item));
		$this->alma_settings->excluded_products_list = array( 'slug1' );

		$this->core_factory->shouldReceive('has_term')->andReturn(true);

		$this->php_helper->shouldReceive('property_exists')->andReturn(true);
		$this->gateway_helper_builder->shouldReceive('get_php_helper')->andReturn($this->php_helper);
		$this->gateway_helper_builder->shouldReceive('get_cart_factory')->andReturn($this->cart_factory);
		$this->gateway_helper_builder->shouldReceive('get_alma_settings')->andReturn($this->alma_settings);
		$this->gateway_helper_builder->shouldReceive('get_core_factory')->andReturn($this->core_factory);
		$this->gateway_helper = $this->gateway_helper_builder->get_instance();

		$this->assertTrue($this->gateway_helper->cart_contains_excluded_category());
	}
}
