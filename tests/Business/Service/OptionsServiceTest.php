<?php

namespace Alma\Gateway\Tests\Business\Service;

use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\WooCommerce\Proxy\OptionsProxy;
use WP_UnitTestCase;

class OptionsServiceTest extends WP_UnitTestCase {
	/**
	 * @var mixed
	 */
	private $options_service;

	private $options = array(
		"enabled"      => "yes",
		"live_api_key" => "RhCGCZY7jKqJfH2C9CNBEH6uWkB3+cb2gpnG8aqTVEkzry/jVpV2Tigmfl/uct3q",
		"test_api_key" => "RhCGCZY7jKqJfH2C9CNBEH6uWkB3+cb2gpnG8aqTVEkzry/jVpV2Tigmfl/uct3q",
		"environment"  => "test",
		"debug"        => "yes"
	);

	public function set_up() {

		$options_proxy = \Mockery::mock( OptionsProxy::class )->makePartial();
		$options_proxy
			->shouldReceive( 'get_options' )
			->andReturn( $this->options );
		$this->options_service = new OptionsService( new EncryptorHelper(), $options_proxy );
	}

	public function test_core_options() {
		$this->assertEquals( $this->options, $this->options_service->get_options() );
		$this->assertEquals( OptionsService::ALMA_ENVIRONMENT_TEST, $this->options_service->get_environment() );
		$this->assertTrue( $this->options_service->is_test() );
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

	public function tear_down() {
		\Mockery::close();
	}

}
