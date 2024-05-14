<?php
/**
 * Class ToolsHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\ToolsHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Factories\CurrencyFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\ToolsHelper
 */
class ToolsHelperTest extends WP_UnitTestCase {

	/**
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	public function set_up() {
		$tools_helper_builder = new ToolsHelperBuilder();
		$this->tools_helper = $tools_helper_builder->get_instance();
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::is_amount_plan_key
	 *
	 * @return void
	 */
	public function test_is_amount_plan_key() {

		$result = $this->tools_helper->is_amount_plan_key( 'min_amount_general_15_1_0' );
		$this->assertTrue( $result );

		$result = $this->tools_helper->is_amount_plan_key( 'min_amount_pos_15_1_0' );
		$this->assertFalse( $result );
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_price_to_cents
	 *
	 * @return void
	 */
	public function test_alma_price_to_cents() {

		$result = $this->tools_helper->alma_price_to_cents( '1.0999' );
		$this->assertEquals( 110, $result );
	}

	/**
	 *
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_format_percent_from_bps
	 * @return void
	 */
	public function test_alma_format_percent_from_bps() {
		// BPS positive
		$price_factory = \Mockery::mock(PriceFactory::class);
		$price_factory->shouldReceive('get_woo_decimals')->andReturn('2');
		$price_factory->shouldReceive('get_woo_decimal_separator')->andReturn(',');
		$price_factory->shouldReceive('get_woo_thousand_separator')->andReturn('.');
		$price_factory->shouldReceive('get_woo_format')->andReturn('%2$s&nbsp;%1$s');

		$tools_helper_builder = \Mockery::mock(ToolsHelperBuilder::class)->makePartial();
		$tools_helper_builder->shouldReceive('get_price_factory')
			->andReturn($price_factory);
		$tools_helper = $tools_helper_builder->get_instance();
		$result = $tools_helper->alma_format_percent_from_bps( '100000' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount">1.000,00&nbsp;<span class="woocommerce-Price-currencySymbol">&#37;</span></span>', $result );

		// BPS negative
		$price_factory = \Mockery::mock(PriceFactory::class);
		$price_factory->shouldReceive('get_woo_decimals')->andReturn('3');
		$price_factory->shouldReceive('get_woo_decimal_separator')->andReturn(' ');
		$price_factory->shouldReceive('get_woo_thousand_separator')->andReturn(' ');
		$price_factory->shouldReceive('get_woo_format')->andReturn('%2$s&nbsp;%1$s');
		$tools_helper_builder = \Mockery::mock(ToolsHelperBuilder::class)->makePartial();
		$tools_helper_builder->shouldReceive('get_price_factory')
		                     ->andReturn($price_factory);
		$tools_helper = $tools_helper_builder->get_instance();
		$result = $tools_helper->alma_format_percent_from_bps( '-200000' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount">-2 000 000&nbsp;<span class="woocommerce-Price-currencySymbol">&#37;</span></span>', $result );
	}

	/**
	 *
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_price_from_cents
	 * @return void
	 */
	public function test_alma_price_from_cents() {

		$result = $this->tools_helper->alma_price_from_cents( 10000 );
		$this->assertEquals( 100, $result );
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_format_price_from_cents
	 * @return void
	 */
	public function test_alma_format_price_from_cents() {

		$result = $this->tools_helper->alma_format_price_from_cents( 10000 );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&euro;</span>100.00</bdi></span>', $result );
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_string_to_bool
	 * @return void
	 */
	public function test_alma_string_to_bool() {

		$result = ToolsHelper::alma_string_to_bool( 'yes' );
		$this->assertTrue( $result );

		$result = ToolsHelper::alma_string_to_bool( 'no' );
		$this->assertFalse( $result );

		$result = ToolsHelper::alma_string_to_bool( true );
		$this->assertTrue( $result );

		$result = ToolsHelper::alma_string_to_bool( false );
		$this->assertFalse( $result );

		$result = ToolsHelper::alma_string_to_bool( 'YES' );
		$this->assertTrue( $result );

		$result = ToolsHelper::alma_string_to_bool( 'NO' );
		$this->assertFalse( $result );

		$result = ToolsHelper::alma_string_to_bool( 'true' );
		$this->assertTrue( $result );

		$result = ToolsHelper::alma_string_to_bool( 'test' );
		$this->assertFalse( $result );

	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::url_for_webhook
	 * @return void
	 */
	public function test_url_for_webhook() {
		$result = $this->tools_helper->url_for_webhook( ConstantsHelper::CUSTOMER_RETURN );
		$this->assertEquals( 'http://example.org/?wc-api=alma_customer_return', $result );
	}


	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::action_for_webhook
	 * @return void
	 */
	public function test_action_for_webhook() {
		$result = ToolsHelper::action_for_webhook( ConstantsHelper::CUSTOMER_RETURN );
		$this->assertEquals( 'woocommerce_api_alma_customer_return', $result );
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::check_currency
	 * @return void
	 */
	public function test_check_currency() {
		// Test Euros
		$currency_factory = \Mockery::mock(CurrencyFactory::class);
		$currency_factory->shouldReceive('get_currency')->andReturn('EUR');
		$tools_helper_builder = \Mockery::mock(ToolsHelperBuilder::class)->makePartial();
		$tools_helper_builder->shouldReceive('get_currency_factory')
		                     ->andReturn($currency_factory);
		$tools_helper = $tools_helper_builder->get_instance();

		$this->assertTrue($tools_helper->check_currency());

		// Test not Euros
		$currency_factory = \Mockery::mock(CurrencyFactory::class);
		$currency_factory->shouldReceive('get_currency')->andReturn('DOL');

		$logger = \Mockery::mock(AlmaLogger::class);
		                  $logger->shouldReceive('warning')
		                  ->with('Currency not supported - Not displaying by Alma.', array('Currency' => 'DOL'));

		$tools_helper_builder = \Mockery::mock(ToolsHelperBuilder::class)->makePartial();
		$tools_helper_builder->shouldReceive('get_currency_factory')
		                     ->andReturn($currency_factory);
		$tools_helper_builder->shouldReceive('get_alma_logger')
		                     ->andReturn($logger);
		$tools_helper = $tools_helper_builder->get_instance();

		$this->assertFalse($tools_helper->check_currency());

	}
}
