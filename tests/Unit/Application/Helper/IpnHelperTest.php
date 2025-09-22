<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Exception\Service\IpnServiceException;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Service\IpnService;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class IpnHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $ipnHelper;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->ipnHelper = new IpnHelper();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();

		$this->ipnHelper = null;
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testConfigureIpnCallback() {
		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );

		$containerMock = Mockery::mock();

		$ipnServiceMock = Mockery::mock( IpnService::class );

		$containerMock->shouldReceive( 'get' )
		              ->with( IpnService::class )
		              ->andReturn( $ipnServiceMock );

		$pluginMock->shouldReceive( 'get_container' )
		           ->once()
		           ->andReturn( $containerMock );

		Functions\expect( 'add_action' )
			->once()
			->withArgs( function ( $event, $callback, $priority, $acceptedArgs ) use ( $ipnServiceMock ) {
				$this->assertSame( 'woocommerce_api_alma_ipn_callback', $event );
				$this->assertSame( 10, $priority );
				$this->assertSame( 1, $acceptedArgs );

				// Vérifier le callback
				$this->assertIsArray( $callback );
				$this->assertSame( $ipnServiceMock, $callback[0] );
				$this->assertSame( 'handleIpnCallback', $callback[1] );

				return true;
			} );

		$ipnHelper = new IpnHelper();
		$ipnHelper->configureIpnCallback();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testConfigureCustomerReturn() {
		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );

		$containerMock = Mockery::mock();

		$ipnServiceMock = Mockery::mock( IpnService::class );

		$containerMock->shouldReceive( 'get' )
		              ->with( IpnService::class )
		              ->andReturn( $ipnServiceMock );

		$pluginMock->shouldReceive( 'get_container' )
		           ->once()
		           ->andReturn( $containerMock );

		Functions\expect( 'add_action' )
			->once()
			->withArgs( function ( $event, $callback, $priority, $acceptedArgs ) use ( $ipnServiceMock ) {
				$this->assertSame( 'woocommerce_api_alma_customer_return', $event );
				$this->assertSame( 10, $priority );
				$this->assertSame( 1, $acceptedArgs );

				// Vérifier le callback
				$this->assertIsArray( $callback );
				$this->assertSame( $ipnServiceMock, $callback[0] );
				$this->assertSame( 'handleCustomerReturn', $callback[1] );

				return true;
			} );

		$ipnHelper = new IpnHelper();
		$ipnHelper->configureCustomerReturn();
	}

	/**
	 * @dataProvider validateSignatureExceptionDataProvider
	 */
	public function testValidateSignatureException( $paymentId, $apiKey, $signature ) {
		$this->expectException( IpnServiceException::class );
		$this->expectExceptionMessage( '[ALMA] Missing required parameters' );
		$this->ipnHelper->validateIpnSignature( $paymentId, $apiKey, $signature );

	}

	public function testValidateSignatureInvalid() {
		$this->expectException( IpnServiceException::class );
		$this->expectExceptionMessage( '[ALMA] Invalid signature' );
		$this->ipnHelper->validateIpnSignature( 'validPaymentId', 'validApiKey', 'invalidSignature' );
	}

	public function testValidateSignature() {

		$this->assertNull(
			$this->ipnHelper->validateIpnSignature(
				'validPaymentId',
				'validApiKey',
				'e930c5376330747939e8b7422b18d3f6538916107955a78032ca32447ceaf8d5'
			)
		);
	}

	public function testParameterErrorWithoutCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Payment validation error: no ID provided.' ], $response );
				$this->assertSame( 400, $code );

				return true;
			} );
		$this->ipnHelper->parameterError();
	}

	public function testParameterErrorWithCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Test Message no id' ], $response );
				$this->assertSame( 400, $code );

				return true;
			} );
		$this->ipnHelper->parameterError( 'Test Message no id' );
	}

	public function testSignatureNotExistErrorWithoutCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Header key X-Alma-Signature does not exist.' ], $response );
				$this->assertSame( 401, $code );

				return true;
			} );
		$this->ipnHelper->signatureNotExistError();
	}

	public function testSignatureNotExistErrorWithCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Test Message no signature' ], $response );
				$this->assertSame( 401, $code );

				return true;
			} );
		$this->ipnHelper->signatureNotExistError( 'Test Message no signature' );
	}

	public function testUnauthorizedErrorWithoutCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Unauthorized request.' ], $response );
				$this->assertSame( 401, $code );

				return true;
			} );
		$this->ipnHelper->unauthorizedError();
	}

	public function testUnauthorizedErrorWithCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Test Message unauthorized' ], $response );
				$this->assertSame( 401, $code );

				return true;
			} );
		$this->ipnHelper->unauthorizedError( 'Test Message unauthorized' );
	}

	public function testPotentialFraudErrorWithoutCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Potential fraud detected.' ], $response );
				$this->assertSame( 400, $code );

				return true;
			} );
		$this->ipnHelper->potentialFraudError();
	}

	public function testPotentialFraudErrorWithCustomMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'error' => 'Test Message potential Fraud' ], $response );
				$this->assertSame( 400, $code );

				return true;
			} );
		$this->ipnHelper->potentialFraudError( 'Test Message potential Fraud' );
	}

	public function testSuccessMessage() {
		Functions\expect( 'wp_send_json' )
			->once()
			->withArgs( function ( $response, $code ) {

				$this->assertSame( [ 'success' => true ], $response );
				$this->assertSame( 200, $code );

				return true;
			} );
		$this->ipnHelper->success();
	}

	public function validateSignatureExceptionDataProvider(): array {
		return [
			[
				'paymentId' => '',
				'apiKey'    => 'validApiKey',
				'signature' => 'validSignature'
			],
			[
				'paymentId' => 'validPaymentId',
				'apiKey'    => '',
				'signature' => 'validSignature'
			],
			[
				'paymentId' => 'validPaymentId',
				'apiKey'    => 'validApiKey',
				'signature' => ''
			]
		];
	}
}

