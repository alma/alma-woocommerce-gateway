<?php

namespace Alma\Gateway\Tests\Unit\Application\Service;

use Alma\Client\Application\ClientConfiguration;
use Alma\Client\Application\CurlClient;
use Alma\Client\Application\Endpoint\MerchantEndpoint;
use Alma\Client\Application\Exception\Endpoint\MerchantEndpointException;
use Alma\Client\Application\Response;
use Alma\Client\Domain\ValueObject\Environment;
use Alma\Gateway\Application\Service\AuthenticationService;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * A successful MerchantEndpoint::me() call returns the merchant id.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testCheckAuthenticationReturnsMerchantIdOnSuccess(): void {
		$merchant = Mockery::mock();
		$merchant->shouldReceive( 'getId' )->andReturn( 'merchant_123' );

		Mockery::mock( 'overload:' . ClientConfiguration::class );
		Mockery::mock( 'overload:' . CurlClient::class );
		$endpoint = Mockery::mock( 'overload:' . MerchantEndpoint::class );
		$endpoint->shouldReceive( 'me' )->once()->andReturn( $merchant );

		$logger = Mockery::mock( LoggerService::class );
		$logger->shouldNotReceive( 'error' );

		$service = new AuthenticationService( $logger );

		$merchantId = $service->checkAuthentication( 'sk_live_key', new Environment( Environment::LIVE_MODE ) );

		$this->assertSame( 'merchant_123', $merchantId );
	}

	/**
	 * When me() fails, the real cause is logged (mode + HTTP status + message) and
	 * an empty merchant id is returned, instead of swallowing the cause behind the
	 * generic "key not valid" message (see ECOM-4278).
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testCheckAuthenticationLogsRealCauseAndReturnsEmptyOnFailure(): void {
		$exception = new MerchantEndpointException( 'Unauthorized', null, new Response( 401 ) );

		Mockery::mock( 'overload:' . ClientConfiguration::class );
		Mockery::mock( 'overload:' . CurlClient::class );
		$endpoint = Mockery::mock( 'overload:' . MerchantEndpoint::class );
		$endpoint->shouldReceive( 'me' )->once()->andThrow( $exception );

		$logger = Mockery::mock( LoggerService::class );
		$logger->shouldReceive( 'error' )
		       ->once()
		       ->with(
			       Mockery::on(
				       function ( $message ) {
					       return strpos( $message, 'mode: live' ) !== false
					              && strpos( $message, 'HTTP status: 401' ) !== false
					              && strpos( $message, 'Unauthorized' ) !== false;
				       }
			       ),
			       Mockery::type( 'array' )
		       );

		$service = new AuthenticationService( $logger );

		$merchantId = $service->checkAuthentication( 'sk_live_key', new Environment( Environment::LIVE_MODE ) );

		$this->assertSame( '', $merchantId );
	}

	/**
	 * When the endpoint fails without a response (e.g. a transport/cURL error), the
	 * HTTP status falls back to 0 and the transport message is still logged.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testCheckAuthenticationLogsTransportErrorWithoutResponse(): void {
		$exception = new MerchantEndpointException( 'cURL error 28: Connection timed out' );

		Mockery::mock( 'overload:' . ClientConfiguration::class );
		Mockery::mock( 'overload:' . CurlClient::class );
		$endpoint = Mockery::mock( 'overload:' . MerchantEndpoint::class );
		$endpoint->shouldReceive( 'me' )->once()->andThrow( $exception );

		$logger = Mockery::mock( LoggerService::class );
		$logger->shouldReceive( 'error' )
		       ->once()
		       ->with(
			       Mockery::on(
				       function ( $message ) {
					       return strpos( $message, 'HTTP status: 0' ) !== false
					              && strpos( $message, 'cURL error 28' ) !== false;
				       }
			       ),
			       Mockery::type( 'array' )
		       );

		$service = new AuthenticationService( $logger );

		$merchantId = $service->checkAuthentication( 'sk_test_key', new Environment( Environment::TEST_MODE ) );

		$this->assertSame( '', $merchantId );
	}
}