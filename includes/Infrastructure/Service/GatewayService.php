<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Repository\UserRepository;
use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

class GatewayService {

	/** GatewayHelper */
	private GatewayHelper $gatewayHelper;

	/** @var EligibilityProvider|null The Eligibility Service if available */
	private ?EligibilityProvider $eligibilityProvider = null;

	/** @var GatewayRepository The Gateway Repository */
	private GatewayRepository $gatewayRepository;

	/** @var FeePlanRepository The Fee Plan Repository */
	private FeePlanRepository $feePlanRepository;

	/** @var ConfigService The Config Service */
	private ConfigService $configService;

	public function __construct(
		ConfigService $configService,
		GatewayRepository $gatewayRepository,
		FeePlanRepository $feePlanRepository,
		EligibilityProvider $eligibilityProvider,
		GatewayHelper $gatewayHelper // Move
	) {
		$this->configService       = $configService;
		$this->gatewayRepository   = $gatewayRepository;
		$this->eligibilityProvider = $eligibilityProvider;
		$this->feePlanRepository   = $feePlanRepository;
		$this->gatewayHelper       = $gatewayHelper;
	}

	/**
	 * Run services on admin init.
	 */
	public function runService() {
		GatewayHelper::runGatewayServices(
			function () {
				// Init Gateway Services
				$this->loadGateway();
				$this->configureReturns();
			}
		);
	}

	public function runUnconfiguredService() {
		GatewayHelper::runGatewayServices(
			function () {
				$this->loadUnconfiguredGateway();
			}
		);
	}

	/**
	 * Load the admin gateway to do configuration.
	 * Load only in admin area on gateway settings page
	 */
	public function loadUnconfiguredGateway() {
		if ( ContextHelper::isAdmin() ) {
			if ( ContextHelper::isGatewaySettingsPage() ) {
				BackendHelper::loadBackendGateway();
			}
		}
	}

	/**
	 * Init and Load the gateways
	 *
	 * Load the Frontend gateways if the user is not in the admin area.
	 * Load the Backend gateways if the user is in the admin area.
	 * But also load the Frontend gateways if the user is in the admin area but not on the Gateway settings page.
	 * It's useful to do refunds on, the order page for example.
	 */
	public function loadGateway() {
		// Init Gateway
		if ( ContextHelper::isAdmin() ) {
			if ( ContextHelper::isGatewaySettingsPage() ) {
				BackendHelper::loadBackendGateway();
			} else {
				FrontendHelper::loadFrontendGateways();
			}
			// Add links to gateway.
			$this->gatewayHelper->addGatewayLinks(
				PluginHelper::getPluginFile(),
				array( $this, 'pluginActionLinks' )
			);
		} else {
			FrontendHelper::loadFrontendGateways();
		}

		if ( ContextHelper::isCheckoutPageUseBlocks() ) {
			$this->initGatewayBlocks();
		}

		// Configure the hooks linked to the gateways
		EventHelper::addEvent( 'woocommerce_order_status_changed',
			array( $this, 'woocommerceOrderStatusChanged' ), 10, 3 );
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
	 * @throws GatewayServiceException
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

				try {
					$paymentService->refundPayment(
						$order->getPaymentId(),
						( new RefundDto() )
							->setAmount( DisplayHelper::price_to_cent( $order->getRemainingRefundAmount() ) )
							->setMerchantReference( $order->getOrderNumber() )
							->setComment( L10nHelper::__( 'Full refund requested by the merchant' ) )
					);
				} catch ( ParametersException $e ) {
					throw new GatewayServiceException( $e->getMessage() );
				}

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
	 */
	public function initGatewayBlocks() {

		if ( ContextHelper::isCheckoutPageUseBlocks() ) {

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( PaymentMethodRegistry $payment_method_registry ) {
					// Register an instance of Alma_Gateway_Blocks.
					$gatewayRepository = Plugin::get_container()->get( GatewayRepository::class );
					foreach ( $gatewayRepository->findAllAlmaGatewayBlocks() as $gateway ) {
						$payment_method_registry->register( $gateway );
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
