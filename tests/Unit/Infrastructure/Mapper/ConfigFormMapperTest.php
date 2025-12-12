<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Mapper;

use Alma\Gateway\Application\Entity\Form\FeePlanConfiguration;
use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Entity\Form\KeyConfiguration;
use Alma\Gateway\Application\Service\AuthenticationService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Mapper\ConfigFormMapper;
use PHPUnit\Framework\TestCase;

class ConfigFormMapperTest extends TestCase {

	private ConfigFormMapper $configFormMapper;

	/** * @var array $additionalSettings */
	private array $additionalSettings;

	/** * @var array */
	private array $feePlanSettings;

	/** * @var array */
	private array $keySettings;

	/** * @var array $settings */
	private array $settings;

	/** * @var ConfigService */
	private $configServiceMock;
	/**
	 * @var array|int[]
	 */
	private array $additionalSettingsWithFeePlan;

	public function setUp(): void {

		$authenticationServiceMock = $this->createMock( AuthenticationService::class );
		$this->configServiceMock         = $this->createMock( ConfigService::class );
		$this->configFormMapper    = new ConfigFormMapper( $this->configServiceMock, $authenticationServiceMock );

		$this->keySettings        = [
			'test_api_key' => 'test_key_123',
			'live_api_key' => 'live_key_456',
		];
		$this->feePlanSettings    = [
			'general_2_0_0_min_amount' => 100,
			'general_2_0_0_max_amount' => 1000,
			'general_2_0_0_enabled'    => true,
			'general_3_0_0_min_amount' => 200,
			'general_3_0_0_max_amount' => 2000,
			'general_3_0_0_enabled'    => false,
		];
		$this->additionalSettings = [
			'debug'                     => 'yes',
			'enabled'                   => true,
			'environment'               => 'test',
			'excluded_products_list'    => '',
			'excluded_products_message' => '',
			'widget_cart_enabled'       => true,
			'widget_product_enabled'    => true
		];
		$this->settings           = array_merge( $this->keySettings, $this->feePlanSettings,
			$this->additionalSettings );

		$this->additionalSettingsWithFeePlan           = array_merge( $this->feePlanSettings,
			$this->additionalSettings );

		$this->finalSettings = array_merge( $this->settings, [ 'merchant_id' => 'merchant_xxxxxxxxxxxxxxx' ] );
	}

	/**
	 * Test to transform Form data to GatewayConfiguration entity if is configured.
	 */
	public function testFromCmsFormIsConfigured(): void {

		$this->configServiceMock->expects( $this->once() )->method('isConfigured')->willReturn( true );
		$gatewayConfig = $this->configFormMapper->from_cms_form( $this->settings );

		$this->assertEquals( $this->additionalSettings, $gatewayConfig->getAdditionalSettings() );
	}

	/**
	 * Test to transform Form data to GatewayConfiguration entity if is not configured.
	 */
	public function testFromCmsFormIsNotConfigured(): void {

		$this->configServiceMock->expects( $this->once() )->method('isConfigured')->willReturn( false );
		$gatewayConfig = $this->configFormMapper->from_cms_form( $this->settings );

		$this->assertEquals( $this->additionalSettingsWithFeePlan, $gatewayConfig->getAdditionalSettings() );
	}

	/**
	 * Test to transform back GatewayConfiguration entity to Form data.
	 */
	public function testToCmsForm(): void {

		$keyConfigurationMock = $this->createMock( KeyConfiguration::class );
		$keyConfigurationMock->method( 'getNewTestKey' )->willReturn( $this->keySettings['test_api_key'] );
		$keyConfigurationMock->method( 'getNewLiveKey' )->willReturn( $this->keySettings['live_api_key'] );
		$keyConfigurationMock->method( 'getNewMerchantId' )->willReturn( 'merchant_xxxxxxxxxxxxxxx' );

		$feePlanConfigurationMock2 = $this->createMock( FeePlanConfiguration::class );
		$feePlanConfigurationMock2->method( 'getPlanKey' )->willReturn( 'general_2_0_0' );
		$feePlanConfigurationMock2->method( 'getMinAmount' )->willReturn( $this->feePlanSettings['general_2_0_0_min_amount'] );
		$feePlanConfigurationMock2->method( 'getMaxAmount' )->willReturn( $this->feePlanSettings['general_2_0_0_max_amount'] );
		$feePlanConfigurationMock2->method( 'isEnabled' )->willReturn( $this->feePlanSettings['general_2_0_0_enabled'] );
		$feePlanConfigurationMock3 = $this->createMock( FeePlanConfiguration::class );
		$feePlanConfigurationMock3->method( 'getPlanKey' )->willReturn( 'general_3_0_0' );
		$feePlanConfigurationMock3->method( 'getMinAmount' )->willReturn( $this->feePlanSettings['general_3_0_0_min_amount'] );
		$feePlanConfigurationMock3->method( 'getMaxAmount' )->willReturn( $this->feePlanSettings['general_3_0_0_max_amount'] );
		$feePlanConfigurationMock3->method( 'isEnabled' )->willReturn( $this->feePlanSettings['general_3_0_0_enabled'] );

		$feePlanConfigurationList = new FeePlanConfigurationList( [
			$feePlanConfigurationMock2,
			$feePlanConfigurationMock3
		] );

		$gatewayConfiguration = new GatewayConfigurationForm( $keyConfigurationMock, $feePlanConfigurationList,
			$this->additionalSettings );

		$validatedSettings = $this->configFormMapper->to_cms_form( $gatewayConfiguration );
		$this->assertEquals( $this->finalSettings, $validatedSettings );
	}

}
