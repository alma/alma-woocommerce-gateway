<?php

namespace Alma\Gateway\Tests\Unit\Application\Entity;

use Alma\Gateway\Application\Entity\Form\KeyConfiguration;
use Alma\Gateway\Application\Service\AuthenticationService;
use Alma\Gateway\Application\Service\ConfigService;
use PHPUnit\Framework\TestCase;

class KeysConfigFormTest extends TestCase {

	/** @var AuthenticationService $authenticationServiceMock */
	private AuthenticationService $authenticationServiceMock;

	public static function fromEmptyConfigurationProvider(): array {
		return [
			/** The plugin has just been installed, there's no configuration, and empty keys are submitted. */
			'empty keys'    => [
				'before' => [
					'newTestKey'              => '',
					'newLiveKey'              => '',
					'expectedTestChanged'     => false,
					'expectedLiveChanged'     => false,
					'expectedTestEmpty'       => true,
					'expectedLiveEmpty'       => true,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
				'after'  => [
					'newTestKey'              => '',
					'newLiveKey'              => '',
					'expectedTestChanged'     => false,
					'expectedLiveChanged'     => false,
					'expectedTestEmpty'       => true,
					'expectedLiveEmpty'       => true,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
			],
			/** The plugin isn't configured yet, and good keys are submitted. */
			'good keys'     => [
				'before' => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => 'good_key',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
				'after'  => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => 'good_key',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => 'merchant_xxxxxxxxxxxxxxx',
					'expectedMerchantChanged' => true,
				],
			],
			/** The plugin has just been installed, there's no configuration, and only one good test key is submitted. */
			'only test key' => [
				'before' => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => '',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => false,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => true,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
				'after'  => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => '',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => false,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => true,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => 'merchant_xxxxxxxxxxxxxxx',
					'expectedMerchantChanged' => true,
				],
			],
			/** The plugin has just been installed, there's no configuration, and only one good live key is submitted. */
			'only live key' => [
				'before' => [
					'newTestKey'              => '',
					'newLiveKey'              => 'good_key',
					'expectedTestChanged'     => false,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => true,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
				'after'  => [
					'newTestKey'              => '',
					'newLiveKey'              => 'good_key',
					'expectedTestChanged'     => false,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => true,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => 'merchant_xxxxxxxxxxxxxxx',
					'expectedMerchantChanged' => true,
				],
			],
		];
	}

	public static function fromExistingConfigurationProvider(): array {
		return [
			/** The plugin is already configured, and good keys are submitted. */
			'good keys reconfiguration' => [
				'before' => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => 'good_key',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
				'after'  => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => 'good_key',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => 'merchant_xxxxxxxxxxxxxxx',
					'expectedMerchantChanged' => true,
				],
			],
			/** One good key, and another good key from another account are submitted. */
			'mismatched keys'           => [
				'before' => [
					'newTestKey'              => 'good_key',
					'newLiveKey'              => 'good_key_from_another_account',
					'expectedTestChanged'     => true,
					'expectedLiveChanged'     => true,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 0,
					'expectedMerchantId'      => '',
					'expectedMerchantChanged' => false,
				],
				'after'  => [
					'newTestKey'              => 'old_good_test_key',
					'newLiveKey'              => 'old_good_live_key',
					'expectedTestChanged'     => false,
					'expectedLiveChanged'     => false,
					'expectedTestEmpty'       => false,
					'expectedLiveEmpty'       => false,
					'expectedErrorCount'      => 1,
					'expectedMerchantId'      => 'merchant_oldxxxxxxxxxxxx',
					'expectedMerchantChanged' => false,
				],
			],
		];
	}

	/**
	 * @dataProvider fromEmptyConfigurationProvider
	 */
	public function testFromEmptyConfiguration( $before, $after ) {

		$brandNewConfigServiceMock = $this->createMock( ConfigService::class );
		$brandNewConfigServiceMock->method( 'getTestApiKey' )->willReturn( '' );
		$brandNewConfigServiceMock->method( 'getLiveApiKey' )->willReturn( '' );
		$brandNewConfigServiceMock->method( 'getMerchantId' )->willReturn( '' );

		$keyConfiguration = new KeyConfiguration(
			$brandNewConfigServiceMock,
			$this->authenticationServiceMock,
			$before['newTestKey'],
			$before['newLiveKey']
		);

		// Before check
		$this->assertEquals( $before['newTestKey'], $keyConfiguration->getNewTestKey() );
		$this->assertEquals( $before['newLiveKey'], $keyConfiguration->getNewLiveKey() );
		$this->assertEquals( $before['expectedTestChanged'], $keyConfiguration->isTestKeyChanged() );
		$this->assertEquals( $before['expectedLiveChanged'], $keyConfiguration->isLiveKeyChanged() );
		$this->assertEquals( $before['expectedTestEmpty'], $keyConfiguration->isNewTestKeyEmpty() );
		$this->assertEquals( $before['expectedLiveEmpty'], $keyConfiguration->isNewLiveKeyEmpty() );
		$this->assertCount( $before['expectedErrorCount'], $keyConfiguration->getErrors() );
		$this->assertEquals( $before['expectedMerchantId'], $keyConfiguration->getNewMerchantId() );
		$this->assertEquals( $before['expectedMerchantChanged'], $keyConfiguration->isMerchantIdChanged() );

		$keyConfiguration->validate();

		// After check
		$this->assertEquals( $after['newTestKey'], $keyConfiguration->getNewTestKey() );
		$this->assertEquals( $after['newLiveKey'], $keyConfiguration->getNewLiveKey() );
		$this->assertEquals( $after['expectedTestChanged'], $keyConfiguration->isTestKeyChanged() );
		$this->assertEquals( $after['expectedLiveChanged'], $keyConfiguration->isLiveKeyChanged() );
		$this->assertEquals( $after['expectedTestEmpty'], $keyConfiguration->isNewTestKeyEmpty() );
		$this->assertEquals( $after['expectedLiveEmpty'], $keyConfiguration->isNewLiveKeyEmpty() );
		$this->assertCount( $after['expectedErrorCount'], $keyConfiguration->getErrors() );
		$this->assertEquals( $after['expectedMerchantId'], $keyConfiguration->getNewMerchantId() );
		$this->assertEquals( $after['expectedMerchantChanged'], $keyConfiguration->isMerchantIdChanged() );
	}

	/**
	 * @dataProvider fromExistingConfigurationProvider
	 */
	public function testFromExistingConfiguration( $before, $after ) {

		$configServiceMock = $this->createMock( ConfigService::class );
		$configServiceMock->method( 'getTestApiKey' )->willReturn( 'old_good_test_key' );
		$configServiceMock->method( 'getLiveApiKey' )->willReturn( 'old_good_live_key' );
		$configServiceMock->method( 'getMerchantId' )->willReturn( 'merchant_oldxxxxxxxxxxxx' );

		$keyConfiguration = new KeyConfiguration(
			$configServiceMock,
			$this->authenticationServiceMock,
			$before['newTestKey'],
			$before['newLiveKey']
		);

		// Before check
		$this->assertEquals( $before['newTestKey'], $keyConfiguration->getNewTestKey() );
		$this->assertEquals( $before['newLiveKey'], $keyConfiguration->getNewLiveKey() );
		$this->assertEquals( $before['expectedTestChanged'], $keyConfiguration->isTestKeyChanged() );
		$this->assertEquals( $before['expectedLiveChanged'], $keyConfiguration->isLiveKeyChanged() );
		$this->assertEquals( $before['expectedTestEmpty'], $keyConfiguration->isNewTestKeyEmpty() );
		$this->assertEquals( $before['expectedLiveEmpty'], $keyConfiguration->isNewLiveKeyEmpty() );
		$this->assertCount( $before['expectedErrorCount'], $keyConfiguration->getErrors() );

		$keyConfiguration->validate();

		// After check
		$this->assertEquals( $after['newTestKey'], $keyConfiguration->getNewTestKey() );
		$this->assertEquals( $after['newLiveKey'], $keyConfiguration->getNewLiveKey() );
		$this->assertEquals( $after['expectedTestChanged'], $keyConfiguration->isTestKeyChanged() );
		$this->assertEquals( $after['expectedLiveChanged'], $keyConfiguration->isLiveKeyChanged() );
		$this->assertCount( $after['expectedErrorCount'], $keyConfiguration->getErrors() );
		$this->assertEquals( $after['expectedMerchantId'], $keyConfiguration->getNewMerchantId() );
		$this->assertEquals( $after['expectedMerchantChanged'], $keyConfiguration->isMerchantIdChanged() );
	}

	protected function setUp(): void {
		parent::setUp();

		$this->authenticationServiceMock = $this->createMock( AuthenticationService::class );
		$this->authenticationServiceMock->method( 'checkAuthentication' )
		                                ->willReturnCallback( function ( $key ) {
			                                switch ( $key ) {
				                                case 'good_key':
					                                return 'merchant_xxxxxxxxxxxxxxx';
				                                case 'good_key_from_another_account':
					                                return 'merchant_yyyyyyyyyyyyyyy';
				                                default:
					                                return '';
			                                }
		                                } );
	}
}
