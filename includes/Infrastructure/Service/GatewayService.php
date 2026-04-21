<?php

namespace Alma\Gateway\Infrastructure\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Exception\Service\BusinessEventsServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Mapper\RefundMapper;
use Alma\Gateway\Application\Provider\PaymentProviderAwareTrait;
use Alma\Gateway\Application\Provider\PaymentProviderFactory;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Block\Gateway\AbstractGatewayBlock;
use Alma\Gateway\Infrastructure\Exception\Repository\OrderRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Service\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Service\CheckoutServiceException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Repository\UserRepository;
use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Psr\Log\LoggerInterface;

class GatewayService {

	/** Add ability to use PaymentProviderFactory */
	use PaymentProviderAwareTrait;

	private AssetsService $assetsService;

	private GatewayRepository $gatewayRepository;

	private BusinessEventsService $businessEventsService;

	/** @var LoggerInterface */
	private LoggerInterface $loggerService;

	public function __construct(
		PaymentProviderFactory $paymentProviderFactory,
		GatewayRepository $gatewayRepository,
		AssetsService $assetsService,
		BusinessEventsService $businessEventsService,
		LoggerService $loggerService
	) {
		$this->paymentProviderFactory = $paymentProviderFactory;
		$this->gatewayRepository      = $gatewayRepository;
		$this->assetsService          = $assetsService;
		$this->businessEventsService  = $businessEventsService;
		$this->loggerService    = $loggerService ;
	}

	/**
	 * Callback function for the event "order status changed".
	 * This method will make full refund if possible.
	 *
	 * @param int    $orderId Order id.
	 * @param string $oldStatus Old status.
	 * @param string $newStatus New status.
	 *
	 * We need to keep $old_status on the signature for the hook
	 *
	 * @throws GatewayServiceException
	 * @todo move this in a more appropriated service
	 *
	 */
	public function woocommerceOrderStatusChanged(
		int $orderId,
		string $oldStatus,
		string $newStatus
	): void {

		/** @var OrderRepository $orderRepository */
		$orderRepository = Plugin::get_container()->get( OrderRepository::class );
		try {
			$order = $orderRepository->getById( $orderId );
		} catch ( OrderRepositoryException $e ) {
			throw new GatewayServiceException( 'Order not found', 0, $e );
		}

		if ( 'refunded' === $newStatus || 'cancelled' === $newStatus ) {
			if ( $order->isRefundable() ) {
				$this->getPaymentProvider();
				$this->paymentProvider->refundPayment(
					$order->getPaymentId(),
					( new RefundMapper() )->buildRefundDto(
						$order,
						__( 'Full refund requested by the merchant', 'alma-gateway-for-woocommerce' )
					)
				);

				$userRepository = Plugin::get_container()->get( UserRepository::class );
				$currentUser    = $userRepository->getById( ContextHelper::getCurrentUserId() );

				$order->addOrderNote(
					sprintf(
					// translators: %s: Refunded by.
						__( 'Order fully refunded by %s.', 'alma-gateway-for-woocommerce' ),
						$currentUser->getDisplayName()
					)
				);
			}
		}

		try {
			$this->businessEventsService->onOrderConfirmed( $oldStatus, $newStatus, $order );
		} catch ( BusinessEventsServiceException $e ) {
			$this->loggerService->debug($e->getMessage());
		}
	}

	/**
	 * Configure returns (ipn and customer_return ) if configuration is done
	 *
	 * @return void
	 */
	public function configureReturns() {
		IpnHelper::configureIpnCallback();
		IpnHelper::configureCustomerReturn();
	}

	/**
	 * Init the gateway blocks if the blocks are enabled
	 * They're registered on every page but will be displayed only on checkout page
	 * (see AbstractGatewayBlock::is_active())
	 *
	 * @return void
	 */
	public function initGatewayBlocks() {

		if ( ContextHelper::isCheckoutPageUseBlocks() ) {

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $paymentMethodRegistry ) {

					$almaGatewayBlocks = $this->gatewayRepository->findAllAlmaGatewayBlocks();

					// Register an instance of Alma_Gateway_Blocks.
					/** @var AbstractGatewayBlock $gatewayBlock */
					foreach ( $almaGatewayBlocks as $gatewayBlock ) {
						$paymentMethodRegistry->register( $gatewayBlock );
					}
				}
			);

			// Enabled blocks AJAX calls on checkout page
			/** @var CheckoutService $checkout_service */
			$checkout_service = Plugin::get_container()->get( CheckoutService::class );
			$checkout_service->initialize();
		}
	}

	/**
	 * Run the gateway blocks services.
	 * @throws GatewayServiceException
	 */
	public function runGatewayBlocks(): void {

		$almaGatewayBlocks = $this->gatewayRepository->findAllAlmaGatewayBlocks();
		try {
			/** @var CheckoutService $checkoutService */
			$checkoutService = Plugin::get_container()->get( CheckoutService::class );
			$params          = $checkoutService->getCheckoutParams( $almaGatewayBlocks );

			$params['checkout_url'] = ContextHelper::getWebhookUrl( 'alma_checkout_data' );
			$this->assetsService->registerGatewayBlockAssets( $params );
		} catch ( CheckoutServiceException|AssetsServiceException $e ) {
			throw new GatewayServiceException( 'Unable to run Gateway Blocks', 0, $e );
		}
	}
}
