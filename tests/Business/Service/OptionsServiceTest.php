<?php

namespace Alma\Gateway\Tests\Business\Service;

use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\WooCommerce\Proxy\OptionsProxy;
use WP_UnitTestCase;

class OptionsServiceTest extends WP_UnitTestCase {

	private $options_service;
	private $options_proxy;


	public function set_up() {
		$this->options_proxy   = $this->createMock( OptionsProxy::class );
		$this->options_service = new OptionsService( new EncryptorHelper(), $this->options_proxy );
	}

	public function tear_down() {
		$this->options_proxy   = null;
		$this->options_service = null;
	}

	public function test_empty_core_options() {

		$options = array(
			"enabled"      => "yes",
			"live_api_key" => "",
			"test_api_key" => "",
			"environment"  => "test",
			"debug"        => "yes"
		);
		$this->options_proxy->method( 'get_options' )->willReturn( $options );

		$this->assertFalse( $this->options_service->has_keys() );
	}

	/**
	 * @return void
	 */
	public function test_core_options() {

		$options = array(
			"enabled"      => "yes",
			"live_api_key" => "RhCGCZY7jKqJfH2C9CNBEH6uWkB3+cb2gpnG8aqTVEkzry/jVpV2Tigmfl/uct3q",
			"test_api_key" => "RhCGCZY7jKqJfH2C9CNBEH6uWkB3+cb2gpnG8aqTVEkzry/jVpV2Tigmfl/uct3q",
			"environment"  => "test",
			"debug"        => "yes"
		);
		$this->options_proxy->method( 'get_options' )->willReturn( $options );

		$this->assertEquals( OptionsService::ALMA_ENVIRONMENT_TEST, $this->options_service->get_environment() );
		$this->assertFalse( $this->options_service->is_live() );
		$this->assertTrue( $this->options_service->has_keys() );
		$this->assertEquals( "sk_test_6Kz0nCca69Nm6SgrIoaf2s5C",
			$this->options_service->get_test_api_key() );
		$this->assertEquals( "sk_test_6Kz0nCca69Nm6SgrIoaf2s5C",
			$this->options_service->get_live_api_key() );
		$this->assertEquals( "sk_test_6Kz0nCca69Nm6SgrIoaf2s5C",
			$this->options_service->get_active_api_key() );
		$this->assertTrue( $this->options_service->is_debug() );
	}


	/**
	 * @dataProvider is_test_data_provider
	 * @return void
	 *
	 */
	public function test_is_test_option( $options, $response ) {
		$this->options_proxy->method( 'get_options' )->willReturn( $options );

		$this->assertEquals( $response, $this->options_service->is_test() );
	}

	public function is_test_data_provider() {
		return [
			"is test is no"  => [
				"options"   => [
					"environment" => "live"
				],
				"response " => false
			],
			"is test is yes" => [
				"options"   => [
					"environment" => "test"
				],
				"response " => true
			]
		];
	}

}
