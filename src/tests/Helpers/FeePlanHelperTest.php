<?php
/**
 * Class FeePlanHelper
 *
 * @covers \Alma\Woocommerce\Helpers\FeePlanHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;
use Alma\API\Entities\FeePlan;
use Alma\Woocommerce\Helpers\FeePlanHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\FeePlanHelper
 */
class FeePlanHelperTest extends WP_UnitTestCase {

	/**
	 * @var FeePlanHelper
	 */
	protected $fee_plan_helper;
	public function set_up() {
		$this->fee_plan_helper = new FeePlanHelper();
	}

	public function test_get_fee_plan() {
		$fee_plan = new FeePlan(
			[
				'installments_count' => 1,
				'min_purchase_amount' => 500,
			]
		);

		$this->assertEquals('100', $this->fee_plan_helper->get_min_purchase_amount($fee_plan) );

		$fee_plan = new FeePlan(
			[
				'installments_count' => 1,
				'deferred_days' => 10,
				'deferred_months' => 0,
				'min_purchase_amount' => 500,
			]
		);

		$this->assertEquals('500', $this->fee_plan_helper->get_min_purchase_amount($fee_plan) );
	}

	public function test_alma_usort_plan_keys() {
		$plan_key_1 = 'general_1_0_0';
		$plan_key_2 = 'general_1_0_0';

		$this->assertEquals(0, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'test_1_0_0';
		$this->assertEquals(0, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_1_0_0';
		$plan_key_2 = 'test_1_0_0';
		$this->assertEquals(0, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_1_10_0';
		$plan_key_2 = 'general_1_0_0';
		$this->assertEquals(1, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_1_0_0';
		$plan_key_2 = 'general_1_15_0';
		$this->assertEquals(-1, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_1_30_0';
		$plan_key_2 = 'general_1_15_1';
		$this->assertEquals(-1, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_1_15_0';
		$plan_key_2 = 'general_1_30_0';
		$this->assertEquals(-1, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_1_15_0';
		$plan_key_2 = 'general_2_30_0';
		$this->assertEquals(-1, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));

		$plan_key_1 = 'general_2_30_0';
		$plan_key_2 = 'general_1_15_0';
		$this->assertEquals(1, $this->fee_plan_helper->alma_usort_plans_keys($plan_key_1, $plan_key_2));
	}

	public function test_alma_match_plan_key_pattern() {
		$plan_key_1 = 'general_4_0_0';

		$this->assertFalse($this->fee_plan_helper->alma_match_plan_key_pattern($plan_key_1, '/^(test)_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})$/'));
		
		$this->assertEquals(array(
			'key' => $plan_key_1,
			'kind' => 'general',
			'installments' => '4',
			'deferred_days' => '0',
			'deferred_months' => '0',
		) , $this->fee_plan_helper->alma_match_plan_key_pattern($plan_key_1));

	}
}



