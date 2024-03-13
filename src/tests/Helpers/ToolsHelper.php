<?php
/**
 * Class TestSample
 *
 * @package Alma_Gateway_For_Woocommerce
 */


use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;

class ToolsHelper extends WP_UnitTestCase {

	/**
	 * @var Alma_Tools_Helper
	 */
	protected $toolsHelper;
	public function __construct() {
		parent::__construct();

		$this->toolsHelper = new Alma_Tools_Helper();
	}

	public function test_is_amount_plan_key() {

		$result = Alma_Tools_Helper::is_amount_plan_key('min_amount_general_15_1_0');
		$this->assertTrue($result);

		$result = Alma_Tools_Helper::is_amount_plan_key('min_amount_pos_15_1_0');
		$this->assertFalse($result);
	}

	public function test_alma_price_to_cents() {

		$result = $this->toolsHelper->alma_price_to_cents('1.0999');
		$this->assertEquals($result, 110);
	}

	public function test_alma_format_percent_from_bps() {

		$result = Alma_Tools_Helper::alma_format_percent_from_bps('10000');
		$this->assertEquals($result, '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#37;</span>100.00</span>');
	}

	public function test_alma_price_from_cents() {

		$result = Alma_Tools_Helper::alma_price_from_cents(10000);
		$this->assertEquals($result, 100);
	}

	public function test_alma_format_price_from_cents() {

		$result = Alma_Tools_Helper::alma_format_price_from_cents(10000);
		$this->assertEquals($result, '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&euro;</span>100.00</bdi></span>');
	}

	public function test_alma_string_to_bool() {

		$result = Alma_Tools_Helper::alma_string_to_bool('yes');
		$this->assertTrue($result);

		$result = Alma_Tools_Helper::alma_string_to_bool('no');
		$this->assertFalse($result);

		$result = Alma_Tools_Helper::alma_string_to_bool(true);
		$this->assertTrue($result);

		$result = Alma_Tools_Helper::alma_string_to_bool(false);
		$this->assertFalse($result);

		$result = Alma_Tools_Helper::alma_string_to_bool('YES');
		$this->assertTrue($result);

		$result = Alma_Tools_Helper::alma_string_to_bool('NO');
		$this->assertFalse($result);

		$result = Alma_Tools_Helper::alma_string_to_bool('true');
		$this->assertTrue($result);

		$result = Alma_Tools_Helper::alma_string_to_bool('test');
		$this->assertFalse($result);

	}

	public function test_url_for_webhook() {
		$result = $this->toolsHelper->url_for_webhook(Alma_Constants_Helper::CUSTOMER_RETURN);
		$this->assertEquals($result, 'http://example.org/?wc-api=alma_customer_return');
	}

	public function test_action_for_webhook() {
		$result = Alma_Tools_Helper::action_for_webhook(Alma_Constants_Helper::CUSTOMER_RETURN);
		$this->assertEquals($result, 'woocommerce_api_alma_customer_return');
	}

}
