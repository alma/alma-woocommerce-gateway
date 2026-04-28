<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway\Backend;

use Alma\Gateway\Infrastructure\Gateway\Backend\AlmaGateway;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AlmaGatewayTest extends TestCase {

	public function setUp(): void {
		Monkey\setUp();
		Functions\when( '__' )->returnArg();
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * First-save scenario: POST contains only the API keys + minimal fields,
	 * so post-configured fieldsets (in_page_enabled, widget_*_enabled) never
	 * appeared in form_fields and aren't in $settings. Their declared defaults
	 * must be backfilled before persistence so the runtime sees the intended
	 * "yes" instead of falling back to "" (which reads as disabled).
	 */
	public function testApplyPostConfiguredDefaultsBackfillsMissingKeys(): void {
		$gateway = $this->getMockBuilder( AlmaGateway::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'display_fieldset_definitions', 'widget_fieldset' ) )
			->getMock();

		$gateway->method( 'display_fieldset_definitions' )->willReturn(
			array(
				'display_section' => array( 'type' => 'title' ),
				'in_page_enabled' => array( 'type' => 'checkbox', 'default' => 'yes' ),
			)
		);
		$gateway->method( 'widget_fieldset' )->willReturn(
			array(
				'widgets_section'        => array( 'type' => 'title' ),
				'widget_product_enabled' => array( 'type' => 'checkbox', 'default' => 'yes' ),
				'widget_cart_enabled'    => array( 'type' => 'checkbox', 'default' => 'yes' ),
			)
		);

		$settings = array(
			'enabled'      => 'yes',
			'live_api_key' => 'live_xxx',
			'test_api_key' => 'test_xxx',
			'environment'  => 'test',
		);

		$result = $this->invokePrivate( $gateway, 'apply_post_configured_defaults', array( $settings ) );

		$this->assertSame( 'yes', $result['in_page_enabled'] );
		$this->assertSame( 'yes', $result['widget_product_enabled'] );
		$this->assertSame( 'yes', $result['widget_cart_enabled'] );
		// Existing keys preserved verbatim.
		$this->assertSame( 'live_xxx', $result['live_api_key'] );
	}

	/**
	 * Subsequent-save scenario: the user explicitly unchecked the InPage
	 * checkbox, so WooCommerce's native validate_checkbox_field stored 'no'
	 * in $settings before our backfill runs. We MUST NOT overwrite it.
	 */
	public function testApplyPostConfiguredDefaultsDoesNotOverwriteExistingValues(): void {
		$gateway = $this->getMockBuilder( AlmaGateway::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'display_fieldset_definitions', 'widget_fieldset' ) )
			->getMock();

		$gateway->method( 'display_fieldset_definitions' )->willReturn(
			array(
				'in_page_enabled' => array( 'type' => 'checkbox', 'default' => 'yes' ),
			)
		);
		$gateway->method( 'widget_fieldset' )->willReturn(
			array(
				'widget_product_enabled' => array( 'type' => 'checkbox', 'default' => 'yes' ),
			)
		);

		$settings = array(
			'in_page_enabled'        => 'no',
			'widget_product_enabled' => 'no',
		);

		$result = $this->invokePrivate( $gateway, 'apply_post_configured_defaults', array( $settings ) );

		$this->assertSame( 'no', $result['in_page_enabled'] );
		$this->assertSame( 'no', $result['widget_product_enabled'] );
	}

	private function invokePrivate( object $object, string $method, array $args ) {
		$reflection = new ReflectionClass( $object );
		$method     = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $args );
	}
}
