<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Mapper\RefundMapper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Repository\UserRepository;
use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

class GatewayService {

	/** GatewayHelper */
	private GatewayHelper $gatewayHelper;
	private AssetsService $assetsService;

	public function __construct(
		AssetsService $assetsService,
		GatewayHelper $gatewayHelper // Move
	) {
		$this->assetsService = $assetsService;
		$this->gatewayHelper = $gatewayHelper;
	}

	/**
	 * Callback function for the event "order status changed".
	 * This method will make full refund if possible.
	 *
	 * @param int    $orderId Order id.
	 * @param string $old_status Old status (not used but necessary for the callback).
	 * @param string $newStatus New status.
	 *
	 * @sonar We need to keep $old_status on the signature for the hook
	 *
	 * @throws GatewayServiceException|ParametersException
	 * @todo move this in a more appropriated service
	 *
	 */
	public function woocommerceOrderStatusChanged(
		int $orderId,
		string $old_status,// NOSONAR -- We need to keep this signature for the hook
		string $newStatus
	): void {

		/** @var OrderRepository $orderRepository */
		$orderRepository = Plugin::get_container()->get( OrderRepository::class );
		try {
			$order = $orderRepository->getById( $orderId );
		} catch ( ProductRepositoryException $e ) {
			throw new GatewayServiceException( 'Order not found' );
		}

		if ( 'refunded' === $newStatus || 'cancelled' === $newStatus ) {
			if ( $order->isRefundable() ) {

				/** @var PaymentProvider $paymentService */
				$paymentService = Plugin::get_container()->get( PaymentProvider::class );

				$paymentService->refundPayment(
					$order->getPaymentId(),
					( new RefundMapper() )->buildRefundDto(
						$order,
						L10nHelper::__( 'Full refund requested by the merchant' )
					)
				);

				$userRepository = Plugin::get_container()->get( UserRepository::class );
				$currentUser    = $userRepository->getById( ContextHelper::getCurrentUserId() );

				$order->addOrderNote(
					sprintf(
						L10nHelper::__( 'Order fully refunded by %s.' ),
						$currentUser->getDisplayName()
					)
				);
			}
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
	 * Add links to gateway.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function pluginActionLinks( $links ): array {
		$setting_link = ContextHelper::getAdminUrl( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' );
		$plugin_links = array(
			sprintf( '<a href="%s">%s</a>', $setting_link, L10nHelper::__( 'Settings' ) ),
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init the gateway blocks if the blocks are enabled
	 * They're registered on every page but will be displayed only on checkout page
	 * (see AbstractGatewayBlock::is_active())
	 *
	 * @return void
	 * @throws GatewayServiceException
	 */
	public function initGatewayBlocks() {

		if ( ContextHelper::isCheckoutPageUseBlocks() ) {

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $paymentMethodRegistry ) {

					// Register an instance of Alma_Gateway_Blocks.
					/** @var GatewayRepository $gatewayRepository */
					$gatewayRepository = Plugin::get_container()->get( GatewayRepository::class );
					$almaGatewayBlocks = $gatewayRepository->findAllAlmaGatewayBlocks();

					foreach ( $almaGatewayBlocks as $gatewayBlock ) {
						$paymentMethodRegistry->register( $gatewayBlock );
					}

					/** @var CheckoutService $checkoutService */
					$checkoutService        = Plugin::get_container()->get( CheckoutService::class );
					$params                 = $checkoutService->getCheckoutParams();
					$params['checkout_url'] = ContextHelper::getWebhookUrl( 'alma_checkout_data' );

					try {
						$this->assetsService->loadGatewayBlockAssets( $params );
					} catch ( AssetsServiceException $e ) {
						throw new GatewayServiceException( 'Unable to load block assets', 0, $e );
					}
				}
			);

			// Enabled blocks AJAX calls on checkout page
			/** @var CheckoutService $checkout_service */
			$checkout_service = Plugin::get_container()->get( CheckoutService::class );
			$checkout_service->initialize();
		}
	}
}
