<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway;

use Alma\Client\Domain\Entity\Payment;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\PaymentService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Plugin is aliased before the gateway is built (its constructor loads Plugin to get the icon URL),
 * so each test runs in a separate process.
 */
class AbstractGatewayProcessPaymentTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private const ORDER_ID   = 42;
	private const PAYMENT_ID = 'payment_123';
	private const PLAN_KEY   = 'general_3_0_0';

	private $sessionHelperMock;

	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg();
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://shop.test/checkout/' );
		Functions\when( 'add_query_arg' )->alias(
			function ( array $args, string $url ) {
				return $url . '?' . http_build_query( $args );
			}
		);

		$this->sessionHelperMock = Mockery::mock( SessionHelper::class );
	}

	public function tearDown(): void {
		Monkey\tearDown();
		Mockery::resetContainer();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * When an account is created at checkout, WooCommerce reloads the guest session and loses
	 * the chosen payment method: it must be forced back to the Alma gateway for the in-page reload.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testProcessPaymentInPageForcesChosenPaymentMethodInSession(): void {
		$gateway = $this->buildGateway( true );

		$this->sessionHelperMock->shouldReceive( 'setSession' )
		                        ->once()
		                        ->with( 'chosen_payment_method', 'alma_test_gateway' );

		$result = $gateway->process_payment( self::ORDER_ID );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( self::PAYMENT_ID, $result['alma_payment_id'] );
		$this->assertStringContainsString( 'alma=inPage', $result['redirect'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testProcessPaymentRedirectDoesNotTouchSession(): void {
		$gateway = $this->buildGateway( false );

		$this->sessionHelperMock->shouldNotReceive( 'setSession' );

		$result = $gateway->process_payment( self::ORDER_ID );

		$this->assertSame(
			array(
				'result'   => 'success',
				'redirect' => 'https://checkout.alma.test/' . self::PAYMENT_ID,
			),
			$result
		);
	}

	/**
	 * Alias Plugin with a container returning mocks for every dependency of process_payment, then build the gateway.
	 */
	private function buildGateway( bool $inPageEnabled ): AbstractGateway {
		$orderMock = Mockery::mock( OrderAdapter::class );
		$orderMock->shouldReceive( 'addOrderNote' );
		$orderMock->shouldReceive( 'updateStatus' );
		$orderMock->shouldReceive( 'update_meta_data' );

		$orderRepositoryMock = Mockery::mock( OrderRepository::class );
		$orderRepositoryMock->shouldReceive( 'getById' )->with( self::ORDER_ID )->andReturn( $orderMock );

		$feePlanAdapterMock = Mockery::mock( FeePlanAdapter::class );
		$feePlanAdapterMock->shouldReceive( 'getLabel' )->andReturn( 'Pay in 3 installments' );
		$feePlanAdapterMock->shouldReceive( 'getPlanKey' )->andReturn( self::PLAN_KEY );

		$feePlanRepositoryMock = Mockery::mock( FeePlanRepository::class );
		$feePlanRepositoryMock->shouldReceive( 'getByPlanKey' )->with( self::PLAN_KEY )->andReturn( $feePlanAdapterMock );

		$paymentMock = Mockery::mock( Payment::class );
		$paymentMock->shouldReceive( 'getId' )->andReturn( self::PAYMENT_ID );
		$paymentMock->shouldReceive( 'getUrl' )->andReturn( 'https://checkout.alma.test/' . self::PAYMENT_ID );

		$paymentServiceMock = Mockery::mock( PaymentService::class );
		$paymentServiceMock->shouldReceive( 'createPayment' )->andReturn( $paymentMock );

		$businessEventsServiceMock = Mockery::mock( BusinessEventsService::class );
		$businessEventsServiceMock->shouldReceive( 'saveAlmaPaymentId' )->with( self::PAYMENT_ID );

		$configServiceMock = Mockery::mock( ConfigService::class );
		$configServiceMock->shouldReceive( 'isInPageEnabled' )->andReturn( $inPageEnabled );

		$services      = array(
			OrderRepository::class       => $orderRepositoryMock,
			FeePlanRepository::class     => $feePlanRepositoryMock,
			ConfigService::class         => $configServiceMock,
			PaymentService::class        => $paymentServiceMock,
			BusinessEventsService::class => $businessEventsServiceMock,
			SessionHelper::class         => $this->sessionHelperMock,
		);
		$containerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )->andReturnUsing(
			function ( string $name ) use ( $services ) {
				return $services[ $name ];
			}
		);

		$pluginInstanceMock = Mockery::mock();
		$pluginInstanceMock->shouldReceive( 'get_plugin_url' )->andReturn( 'https://shop.test/wp-content/plugins/alma/' );

		$pluginMock = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$pluginMock->shouldReceive( 'get_container' )->andReturn( $containerMock );
		$pluginMock->shouldReceive( 'get_instance' )->andReturn( $pluginInstanceMock );

		return new class( $feePlanRepositoryMock, Mockery::mock( LoggerService::class ) ) extends AbstractGateway {
			protected const PAYMENT_METHOD = 'test';

			public function process_payment_fields( OrderAdapterInterface $order ): array {
				return array( 'alma_plan_key' => 'general_3_0_0' );
			}
		};
	}
}
