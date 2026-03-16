<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class FrontendHelperTest extends TestCase {

	private const ALMA_PAY_NOW   = 'alma_pay_now_gateway';
	private const ALMA_PNX       = 'alma_pnx_gateway';
	private const ALMA_PAY_LATER = 'alma_pay_later_gateway';
	private const ALMA_CREDIT    = 'alma_credit_gateway';
	private const ALMA_CONFIG    = 'alma_config_gateway';

	protected function setUp(): void {
		Monkey\setUp();

		// Set static properties via reflection to avoid dependency on PaymentMethod constants.
		$ref = new \ReflectionProperty( FrontendHelper::class, 'alma_gateway_ids' );
		$ref->setAccessible( true );
		$ref->setValue( null, [
			self::ALMA_PAY_NOW,
			self::ALMA_PNX,
			self::ALMA_PAY_LATER,
			self::ALMA_CREDIT,
		] );

		$ref = new \ReflectionProperty( FrontendHelper::class, 'config_gateway_id' );
		$ref->setAccessible( true );
		$ref->setValue( null, self::ALMA_CONFIG );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
	}

	// ─── syncGatewayOrder ────────────────────────────────────────────────

	public function testSyncGatewayOrderDefaultsToZeroWhenOrderingIsEmpty() {
		Functions\expect( 'absint' )->andReturnUsing( function ( $val ) {
			return abs( (int) $val );
		} );

		$result = FrontendHelper::syncGatewayOrder( [] );

		foreach ( [ self::ALMA_PAY_NOW, self::ALMA_PNX, self::ALMA_PAY_LATER, self::ALMA_CREDIT ] as $id ) {
			$this->assertSame( 0, $result[ $id ] );
		}
	}

	public function testSyncGatewayOrderUsesConfigGatewayPosition() {
		Functions\expect( 'absint' )->andReturnUsing( function ( $val ) {
			return abs( (int) $val );
		} );

		$ordering = [
			'paypal'           => 0,
			'stripe'           => 1,
			'cod'              => 2,
			self::ALMA_CONFIG  => 3,
		];

		$result = FrontendHelper::syncGatewayOrder( $ordering );

		foreach ( [ self::ALMA_PAY_NOW, self::ALMA_PNX, self::ALMA_PAY_LATER, self::ALMA_CREDIT ] as $id ) {
			$this->assertSame( 3, $result[ $id ] );
		}

		// Other gateways unchanged.
		$this->assertSame( 0, $result['paypal'] );
		$this->assertSame( 1, $result['stripe'] );
		$this->assertSame( 2, $result['cod'] );
	}

	public function testSyncGatewayOrderHandlesNonArrayInput() {
		Functions\expect( 'absint' )->andReturnUsing( function ( $val ) {
			return abs( (int) $val );
		} );

		$result = FrontendHelper::syncGatewayOrder( false );

		$this->assertIsArray( $result );
		foreach ( [ self::ALMA_PAY_NOW, self::ALMA_PNX, self::ALMA_PAY_LATER, self::ALMA_CREDIT ] as $id ) {
			$this->assertSame( 0, $result[ $id ] );
		}
	}

	// ─── sortAlmaGateways ────────────────────────────────────────────────

	public function testSortAlmaGatewaysGroupsScatteredGatewaysAtFirstAlmaPosition() {
		$gateways = [
			'paypal'              => 'paypal_gw',
			self::ALMA_PNX        => 'pnx_gw',
			'stripe'              => 'stripe_gw',
			self::ALMA_PAY_NOW    => 'pay_now_gw',
			self::ALMA_PAY_LATER  => 'pay_later_gw',
			'cod'                 => 'cod_gw',
			self::ALMA_CREDIT     => 'credit_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( [
			'paypal',
			self::ALMA_PAY_NOW,
			self::ALMA_PNX,
			self::ALMA_PAY_LATER,
			self::ALMA_CREDIT,
			'stripe',
			'cod',
		], array_keys( $result ) );
	}

	public function testSortAlmaGatewaysPreservesOtherGatewaysOrder() {
		$gateways = [
			'paypal'              => 'paypal_gw',
			'stripe'              => 'stripe_gw',
			self::ALMA_PAY_NOW    => 'pay_now_gw',
			self::ALMA_PNX        => 'pnx_gw',
			self::ALMA_PAY_LATER  => 'pay_later_gw',
			self::ALMA_CREDIT     => 'credit_gw',
			'cod'                 => 'cod_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( [
			'paypal',
			'stripe',
			self::ALMA_PAY_NOW,
			self::ALMA_PNX,
			self::ALMA_PAY_LATER,
			self::ALMA_CREDIT,
			'cod',
		], array_keys( $result ) );
	}

	public function testSortAlmaGatewaysReturnsUnchangedWhenNoAlmaGateways() {
		$gateways = [
			'paypal' => 'paypal_gw',
			'stripe' => 'stripe_gw',
			'cod'    => 'cod_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( $gateways, $result );
	}

	public function testSortAlmaGatewaysWithOnlyAlmaGateways() {
		$gateways = [
			self::ALMA_CREDIT     => 'credit_gw',
			self::ALMA_PAY_NOW    => 'pay_now_gw',
			self::ALMA_PNX        => 'pnx_gw',
			self::ALMA_PAY_LATER  => 'pay_later_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( [
			self::ALMA_PAY_NOW,
			self::ALMA_PNX,
			self::ALMA_PAY_LATER,
			self::ALMA_CREDIT,
		], array_keys( $result ) );
	}

	public function testSortAlmaGatewaysWithPartialAlmaGateways() {
		$gateways = [
			'paypal'              => 'paypal_gw',
			self::ALMA_CREDIT     => 'credit_gw',
			'stripe'              => 'stripe_gw',
			self::ALMA_PAY_NOW    => 'pay_now_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( [
			'paypal',
			self::ALMA_PAY_NOW,
			self::ALMA_CREDIT,
			'stripe',
		], array_keys( $result ) );
	}

	public function testSortAlmaGatewaysAtEndOfList() {
		$gateways = [
			'paypal'              => 'paypal_gw',
			'stripe'              => 'stripe_gw',
			'cod'                 => 'cod_gw',
			self::ALMA_CREDIT     => 'credit_gw',
			self::ALMA_PAY_NOW    => 'pay_now_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( [
			'paypal',
			'stripe',
			'cod',
			self::ALMA_PAY_NOW,
			self::ALMA_CREDIT,
		], array_keys( $result ) );
	}

	public function testSortAlmaGatewaysAtBeginningOfList() {
		$gateways = [
			self::ALMA_CREDIT     => 'credit_gw',
			self::ALMA_PAY_LATER  => 'pay_later_gw',
			'paypal'              => 'paypal_gw',
			'stripe'              => 'stripe_gw',
		];

		$result = FrontendHelper::sortAlmaGateways( $gateways );

		$this->assertSame( [
			self::ALMA_PAY_LATER,
			self::ALMA_CREDIT,
			'paypal',
			'stripe',
		], array_keys( $result ) );
	}
}

