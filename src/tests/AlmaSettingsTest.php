<?php

namespace Alma\Woocommerce\Tests;

use Alma\Woocommerce\AlmaSettings;
use WP_UnitTestCase;


class AlmaSettingsTest extends WP_UnitTestCase
{
	/**
	 * @var mixed
	 */
	private $alma_settings;

	public function set_up()
	{
		$this->alma_settings = \Mockery::mock(AlmaSettings::class)->makePartial();
	}

	public function test_has_pay_now()
	{
		$this->alma_settings
			->shouldReceive('get_enabled_plans_definitions')
			->andReturn([
				"general_1_0_0" => [
					'installments_count' => 1,
					'min_amount' => 100,
					'max_amount' => 1000,
					'deferred_days' => 0,
					'deferred_months' => 0
				],
				"general_3_0_0" => [
					'installments_count' => 3,
					'min_amount' => 100,
					'max_amount' => 1000,
					'deferred_days' => 0,
					'deferred_months' => 0
				],
			]);
		$this->assertTrue($this->alma_settings->has_pay_now());
	}

	public function test_has_pay_now_with_invalid()
	{
		$this->alma_settings
			->shouldReceive('get_enabled_plans_definitions')
			->andReturn([
				"general_1_15_0" => [
					'installments_count' => 1,
					'min_amount' => 100,
					'max_amount' => 1000,
					'deferred_days' => 15,
					'deferred_months' => 0
				],
				"general_1_0_1" => [
					'installments_count' => 1,
					'min_amount' => 100,
					'max_amount' => 1000,
					'deferred_days' => 0,
					'deferred_months' => 1
				],
				"general_10_0_0" => [
					'installments_count' => 10,
					'min_amount' => 100,
					'max_amount' => 1000,
					'deferred_days' => 0,
					'deferred_months' => 0
				]
			]);
		$this->assertFalse($this->alma_settings->has_pay_now());
	}

	public function tear_down()
	{
		\Mockery::close();
	}

}
