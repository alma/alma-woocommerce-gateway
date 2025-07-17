<?php

namespace Alma\Gateway\Tests\Unit\Business\Service;

use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Service\OptionsService;
use Alma\Gateway\WooCommerce\Proxy\OptionsProxy;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class OptionsServiceTest extends TestCase {

	/**
	 * @throws Exception
	 */
	public function test_empty_core_options() {

		$options            = array(
			"enabled"      => "yes",
			"live_api_key" => "",
			"test_api_key" => "",
			"environment"  => "test",
			"debug"        => "yes"
		);
		$options_proxy_mock = $this->createMock( OptionsProxy::class );
		$options_proxy_mock->method( 'get_options' )->willReturn( $options );
		$options_service = new OptionsService( new EncryptorHelper(), $options_proxy_mock );
		$this->assertFalse( $options_service->has_keys() );
	}

	/**
	 * Test the core of the options of the service.
	 * Pay attention that encrypted keys were encrypted with the NONCE_SALT defined in bootstrap.php. Do not change it.
	 * @return void
	 * @throws Exception
	 */
	public function test_core_options() {

		$options            = array(
			"enabled"      => "yes",
			"live_api_key" => "4B5Hr3zwUWtLrrAzKMgf3YE3+oR9PsGMkUsUhlBnzrQ=",
			"test_api_key" => "4B5Hr3zwUWtLrrAzKMgf3ecFqBmyIu0oIjIiBtPS/1g=",
			"environment"  => "test",
			"debug"        => "yes"
		);
		$options_proxy_mock = $this->createMock( OptionsProxy::class );
		$options_proxy_mock->method( 'get_options' )->willReturn( $options );
		$options_service = new OptionsService( new EncryptorHelper(), $options_proxy_mock );

		$this->assertEquals( OptionsService::ALMA_ENVIRONMENT_TEST, $options_service->get_environment() );
		$this->assertFalse( $options_service->is_live() );
		$this->assertTrue( $options_service->has_keys() );
		$this->assertEquals( "this_is_a_dummy_test_key", $options_service->get_test_api_key() );
		$this->assertEquals( "this_is_a_dummy_live_key", $options_service->get_live_api_key() );
		$this->assertEquals( "this_is_a_dummy_test_key", $options_service->get_active_api_key() );
		$this->assertTrue( $options_service->is_debug() );
	}
}
