<?php

namespace Alma\Gateway\Tests\Unit\Business\Service;

use Alma\API\Domain\Entity\FeePlan;
use Alma\API\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Helper\WordPressHelper;
use Alma\Gateway\Infrastructure\Repository\ConfigRepository;
use Mockery;
use PHPUnit\Framework\TestCase;

class OptionsServiceTest extends TestCase {

	private WordPressHelper $wordpress_proxy_mock;
	private ConfigRepository $options_proxy_mock;

	private ConfigService $options_service;

	public function setUp(): void {
		Mockery::mock( 'alias:Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy' )
		       ->shouldReceive( 'set_key_encryptor' )
		       ->andReturnNull();

		$this->options_proxy_mock   = $this->createMock( ConfigRepository::class );
		$this->wordpress_proxy_mock = Mockery::mock( WordPressHelper::class );
		$this->options_service      = new ConfigService( new EncryptorHelper(), $this->options_proxy_mock );
	}

	public function tearDown(): void {
		Mockery::resetContainer();
		Mockery::close();
	}

	public function test_is_configured() {

		$options = array(
			'enabled'      => 'yes',
			'live_api_key' => '4B5Hr3zwUWtLrrAzKMgf3YE3+oR9PsGMkUsUhlBnzrQ=',
			'test_api_key' => '4B5Hr3zwUWtLrrAzKMgf3ecFqBmyIu0oIjIiBtPS/1g=',
			'environment'  => 'test',
			'debug'        => 'yes'
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertTrue( $this->options_service->isConfigured() );
	}

	public function test_is_not_configured() {

		$options = array(
			'enabled'      => 'yes',
			'live_api_key' => '',
			'test_api_key' => '',
			'environment'  => 'test',
			'debug'        => 'yes'
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertFalse( $this->options_service->isConfigured() );
	}

	public function test_is_completely_not_configured() {

		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );
		$this->assertFalse( $this->options_service->isConfigured() );
	}

	public function test_get_environment() {

		$options = array(
			'environment' => 'test',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( ConfigService::ALMA_ENVIRONMENT_TEST, $this->options_service->getEnvironment() );
	}

	public function test_get_environment_with_no_config() {

		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );
		$this->assertEquals( ConfigService::ALMA_ENVIRONMENT_LIVE, $this->options_service->getEnvironment() );
	}

	public function test_is_test() {

		$options = array(
			'environment' => 'test',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertTrue( $this->options_service->isTest() );
	}

	public function test_is_live() {

		$options = array(
			'environment' => 'live',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertTrue( $this->options_service->isLive() );
	}

	public function test_has_keys() {

		$options = array(
			'enabled'      => 'yes',
			'live_api_key' => '4B5Hr3zwUWtLrrAzKMgf3YE3+oR9PsGMkUsUhlBnzrQ=',
			'test_api_key' => '4B5Hr3zwUWtLrrAzKMgf3ecFqBmyIu0oIjIiBtPS/1g=',
			'environment'  => 'test',
			'debug'        => 'yes'
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertTrue( $this->options_service->hasKeys() );
	}

	public function test_has_no_keys() {

		$options = array(
			'enabled'      => 'yes',
			'live_api_key' => '',
			'test_api_key' => '',
			'environment'  => 'test',
			'debug'        => 'yes'
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertFalse( $this->options_service->hasKeys() );
	}

	public function test_get_active_api_key() {
		$options = array(
			'enabled'      => 'yes',
			'live_api_key' => '4B5Hr3zwUWtLrrAzKMgf3YE3+oR9PsGMkUsUhlBnzrQ=',
			'test_api_key' => '4B5Hr3zwUWtLrrAzKMgf3ecFqBmyIu0oIjIiBtPS/1g=',
			'environment'  => 'test',
			'debug'        => 'yes'
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( 'this_is_a_dummy_test_key', $this->options_service->getActiveApiKey() );
	}

	public function test_get_live_api_key() {
		$options = array(
			'live_api_key' => '4B5Hr3zwUWtLrrAzKMgf3YE3+oR9PsGMkUsUhlBnzrQ=',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( 'this_is_a_dummy_live_key', $this->options_service->getLiveApiKey() );
	}

	public function test_get_live_api_key_with_no_configuration() {
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );
		$this->assertEquals( '', $this->options_service->getLiveApiKey() );
	}

	public function test_get_test_api_key_with_no_configuration() {
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );
		$this->assertEquals( '', $this->options_service->getTestApiKey() );
	}

	public function test_is_debug_with_no_configuration() {
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );
		$this->assertFalse( $this->options_service->isDebug() );
	}

	public function test_is_debug() {
		$options = array(
			'debug' => 'yes',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertTrue( $this->options_service->isDebug() );
	}

	public function test_get_option() {
		$options = array(
			'enabled' => 'yes',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( 'yes', $this->options_service->getSetting( 'enabled' ) );
	}

	public function test_has_option() {
		$options = array(
			'enabled' => 'yes',
		);
		$this->options_proxy_mock->method( 'hasSetting' )->willReturnCallback(
			function ( $option_name ) use ( $options ) {
				if ( array_key_exists( $option_name, $options ) ) {
					return true;
				}

				return false;
			}
		);

		$this->assertTrue( $this->options_service->hasSetting( 'enabled' ) );
		$this->assertFalse( $this->options_service->hasSetting( 'foobar' ) );
	}

	public function test_get_option_with_no_configuration() {
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );
		$this->assertEquals( '', $this->options_service->getSetting( 'enabled' ) );
	}

	public function test_init_fee_plan_list() {
		// Mock the FeePlanList and FeePlan objects
		$fee_plan_list = new FeePlanList();
		$fee_plan      = new FeePlan(
			array(
				'id'                  => 1,
				'enabled'             => true,
				'max_purchase_amount' => 5000,
				'min_purchase_amount' => 10000,
			)
		);
		$fee_plan_list->add( $fee_plan );

		// Mock the get_options method to return the fee plan options
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( [] );

		// Call the init_fee_plan_list method
		$this->options_service->initFeePlanList( $fee_plan_list );
	}

	public function test_toggle_fee_plan() {
		$options = array(
			'general_10_0_0_enabled' => 'yes',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertFalse( $this->options_service->toggleFeePlan( 'general_10_0_0' ) );
	}

	public function test_is_fee_plan_enabled() {
		$options = array(
			'general_10_0_0_enabled' => 'yes',
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( 1, $this->options_service->isFeePlanEnabled( 'general_10_0_0' ) );
	}

	public function test_get_max_amount() {
		$options = array(
			'general_10_0_0_max_amount' => 10000,
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( 10000, $this->options_service->getMaxAmount( 'general_10_0_0' ) );
	}

	public function test_get_min_amount() {
		$options = array(
			'general_10_0_0_min_amount' => 10000,
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( 10000, $this->options_service->getMinAmount( 'general_10_0_0' ) );
	}

	public function test_get_options() {
		$options = array(
			'enabled'      => 'yes',
			'live_api_key' => '4B5Hr3zwUWtLrrAzKMgf3YE3+oR9PsGMkUsUhlBnzrQ=',
			'test_api_key' => '4B5Hr3zwUWtLrrAzKMgf3ecFqBmyIu0oIjIiBtPS/1g=',
			'environment'  => 'test',
			'debug'        => 'yes'
		);
		$this->options_proxy_mock->method( 'getSettings' )->willReturn( $options );
		$this->assertEquals( $options, $this->options_service->getSettings() );
	}

	public function test_delete_option() {
		$options = array(
			'option_to_delete' => true,
		);
		$this->options_proxy_mock->method( 'deleteSetting' )->willReturn( true );
		$this->assertTrue( $this->options_service->deleteSetting( 'option_to_delete' ) );
	}
}
