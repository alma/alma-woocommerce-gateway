<?php
/**
 * Class ToolsHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\ToolsHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

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
	protected $toolsHelper;

	public function __construct() {
		parent::__construct();

		$this->toolsHelper = new ToolsHelper();
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::is_amount_plan_key
	 *
	 * @return void
	 */
	public function test_is_amount_plan_key() {

		$result = ToolsHelper::is_amount_plan_key( 'min_amount_general_15_1_0' );
		$this->assertTrue( $result );

		$result = ToolsHelper::is_amount_plan_key( 'min_amount_pos_15_1_0' );
		$this->assertFalse( $result );
	}

	/**
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_price_to_cents
	 *
	 * @return void
	 */
	public function test_alma_price_to_cents() {

		$result = $this->toolsHelper->alma_price_to_cents( '1.0999' );
		$this->assertEquals( $result, 110 );
	}

	/**
	 *
	 * @covers \Alma\Woocommerce\Helpers\ToolsHelper::alma_format_percent_from_bps
	 * @return void
	 */
	public function test_alma_format_percent_from_bps() {

		$result = ToolsHelper::alma_format_percent_from_bps( '10000' );
		$this->assertEquals( $result, '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#37;</span>100.00</span>' );
	}

	/**
	 *
	 *
	 * @return void
	 */
	public function test_alma_price_from_cents() {

		$result = ToolsHelper::alma_price_from_cents( 10000 );
		$this->assertEquals( $result, 100 );
	}

	/**
	 *
	 *
	 * @return void
	 */
	public function test_alma_format_price_from_cents() {

		$result = ToolsHelper::alma_format_price_from_cents( 10000 );
		$this->assertEquals( $result, '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&euro;</span>100.00</bdi></span>' );
	}

	/**
	 *
	 *
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
	 *
	 *
	 * @return void
	 */
	public function test_url_for_webhook() {
		$result = $this->toolsHelper->url_for_webhook( ConstantsHelper::CUSTOMER_RETURN );
		$this->assertEquals( $result, 'http://example.org/?wc-api=alma_customer_return' );
	}


	/**
	 *
	 *
	 * @return void
	 */
	public function test_action_for_webhook() {
		$result = ToolsHelper::action_for_webhook( ConstantsHelper::CUSTOMER_RETURN );
		$this->assertEquals( $result, 'woocommerce_api_alma_customer_return' );
	}

}
