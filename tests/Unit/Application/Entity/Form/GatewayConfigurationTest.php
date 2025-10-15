<?php

namespace Alma\Gateway\Tests\Unit\Application\Entity\Form;

use Alma\Gateway\Application\Entity\Form\FeePlanConfigurationList;
use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Entity\Form\KeyConfiguration;
use PHPUnit\Framework\TestCase;

class GatewayConfigurationTest extends TestCase {

	/** @var FeePlanConfigurationList $feePlanConfigurationListMock */
	private FeePlanConfigurationList $feePlanConfigurationListMock;

	/** @var KeyConfiguration $keyConfigurationMock */
	private KeyConfiguration $keyConfigurationMock;

	public function setUp(): void {
		$this->keyConfigurationMock = $this->createMock( KeyConfiguration::class );
		$this->keyConfigurationMock->method( 'getErrors' )->willReturn( [ 'first error', 'second error' ] );
		$this->keyConfigurationMock->method( 'isMerchantIdChanged' )->willReturn( false );

		$this->feePlanConfigurationListMock = $this->createMock( FeePlanConfigurationList::class );
		$this->feePlanConfigurationListMock->method( 'getErrors' )->willReturn( [ 'third error' ] );
	}

	public function testKeysConfigForm() {
		$additionalSettings   = [ 'some_setting' => 'some_value' ];
		$gatewayConfiguration = new GatewayConfigurationForm(
			$this->keyConfigurationMock,
			$this->feePlanConfigurationListMock,
			$additionalSettings
		);

		$this->assertSame( $this->keyConfigurationMock, $gatewayConfiguration->getKeyConfiguration() );
		$this->assertSame( $this->feePlanConfigurationListMock, $gatewayConfiguration->getFeePlanConfigurationList() );
		$this->assertSame( $additionalSettings, $gatewayConfiguration->getAdditionalSettings() );
		$this->assertSame( [ 'first error', 'second error', 'third error' ], $gatewayConfiguration->getErrors() );

	}
}
