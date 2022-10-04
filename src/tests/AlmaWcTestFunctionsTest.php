<?php

use PHPUnit\Framework\TestCase;

class AlmaWcTestFunctionsTest extends TestCase {
    public function data_provider() {
        yield [ 'toto_1_0_0', false ];
        yield [ 'n importe quoi', false ];
        yield [
            'general_1_0_0',
            [
                'key'             => 'general_1_0_0',
                'kind'            => 'general',
                'installments'    => '1',
                'deferred_days'   => '0',
            	'deferred_months' => '0',
			]
		];
		yield [
			'pos_1_0_6',
			[
				'key'             => 'pos_1_0_6',
				'kind'            => 'pos',
				'installments'    => '1',
				'deferred_days'   => '0',
				'deferred_months' => '6',
			]
		];
		yield [
			'general_99_99_99',
			[
				'key'             => 'general_99_99_99',
				'kind'            => 'general',
				'installments'    => '99',
				'deferred_days'   => '99',
				'deferred_months' => '99',
			]
		];
	}

	/**
	 * @dataProvider data_provider
	 */
	public function test_alma_wc_match_plan_key_pattern( $plan_key, $expected ) {
		$this->assertEquals( $expected, alma_wc_match_plan_key_pattern( $plan_key ) );
	}

	public function test_alma_wc_usort_pay_later_plans_keys() {
		$array = [
			'general_1_45_0',
			'general_1_15_0',
			'general_1_30_0',
		];
		usort( $array, 'alma_wc_usort_plans_keys' );
		$this->assertEquals(
			[
				'general_1_15_0',
				'general_1_30_0',
				'general_1_45_0',
			],
			$array
		);
	}
	public function test_alma_wc_usort_pnx_plans_keys() {
		$array = [
			'general_4_0_0',
			'general_3_0_0',
			'general_2_0_0',
			'general_5_0_0',
			'general_10_0_0',
			'general_1_0_0',
		];
		usort( $array, 'alma_wc_usort_plans_keys' );
		$this->assertEquals(
			[
				'general_1_0_0',
				'general_2_0_0',
				'general_3_0_0',
				'general_4_0_0',
				'general_5_0_0',
				'general_10_0_0',
			],
			$array
		);
	}
	public function test_alma_wc_usort_deferred_months_plans_keys() {
		$array = [
			'general_1_0_3',
			'general_1_0_1',
			'general_1_0_18',
		];
		usort( $array, 'alma_wc_usort_plans_keys' );
		$this->assertEquals(
			[
				'general_1_0_1',
				'general_1_0_3',
				'general_1_0_18',
			],
			$array
		);
	}
	public function test_alma_wc_usort_pnx_and_pay_later_plans_keys() {
		$array = [
			'general_1_15_0',
			'general_1_30_0',
			'general_4_0_0',
			'general_2_0_0',
			'general_3_0_0',
			'general_5_0_0',
			'general_10_0_0',
			'general_1_0_0',
		];
		usort( $array, 'alma_wc_usort_plans_keys' );
		$this->assertEquals(
			[
				'general_1_0_0',
				'general_2_0_0',
				'general_3_0_0',
				'general_4_0_0',
				'general_5_0_0',
				'general_10_0_0',
				'general_1_15_0',
				'general_1_30_0',
			],
			$array
		);
	}
	public function test_alma_wc_usort_all_plans_keys() {
		$array = [
			'general_1_15_0',
			'general_1_0_3',
			'general_1_30_0',
			'general_1_0_1',
			'general_4_0_0',
			'general_2_0_0',
			'general_3_0_0',
			'general_5_0_0',
			'general_10_0_0',
			'general_1_0_0',
		];
		usort( $array, 'alma_wc_usort_plans_keys' );
		$this->assertEquals(
			[
				'general_1_0_0',
				'general_2_0_0',
				'general_3_0_0',
				'general_4_0_0',
				'general_5_0_0',
				'general_10_0_0',
				'general_1_15_0',
				'general_1_30_0',
				'general_1_0_1',
				'general_1_0_3',
			],
			$array
		);
	}
}
