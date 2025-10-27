<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Mapper\EligibilityMapper;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Provider\FeePlanProvider;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
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

	/** @var FeePlanProvider|null The Fee plan Service if available */
	private ?FeePlanProvider $feePlanProvider = null;

	/** @var IpnHelper The IPN Helper */
	private IpnHelper $ipnHelper;

	/** @var ContextHelperInterface The Context Adapter gives information about context */
	private ContextHelperInterface $contextHelper;

	/** @var GatewayRepository The Gateway Repository */
	private GatewayRepository $gatewayRepository;

	/** @var FrontendHelper The Frontend Helper */
	private FrontendHelper $frontendHelper;

	/** @var BackendHelper The Backend Helper */
	private BackendHelper $backendHelper;

	/** @var ConfigService The Config Service */
	private ConfigService $configService;

	public function __construct(
		ConfigService $configService,
		GatewayRepository $gatewayRepository,
		GatewayHelper $gatewayHelper,// Move
		IpnHelper $ipnHelper,
		ContextHelperInterface $contextHelper,
		FrontendHelper $frontendHelper,
		BackendHelper $backendHelper
	) {
		$this->configService     = $configService;
		$this->gatewayRepository = $gatewayRepository;
		$this->gatewayHelper     = $gatewayHelper;
		$this->ipnHelper         = $ipnHelper;
		$this->contextHelper     = $contextHelper;
		$this->frontendHelper    = $frontendHelper;
		$this->backendHelper     = $backendHelper;
	}

	/**
	 *  Set the eligibility service. This can't be done in the constructor because the service
	 *  could be not available at the time if the plugin in not fully configured.
	 *
	 * @param EligibilityProvider $eligibilityProvider
	 *
	 * @return void
	 */
	public function setEligibilityProvider( EligibilityProvider $eligibilityProvider ): void {
		$this->eligibilityProvider = $eligibilityProvider;
	}

	/**
	 * Set the fee plan service. This can't be done in the constructor because the service
	 * could be not available at the time if the plugin in not fully configured.
	 *
	 * @param FeePlanProvider $feePlanProvider
	 *
	 * @return void
	 */
	public function setFeePlanProvider( FeePlanProvider $feePlanProvider ): void {
		$this->feePlanProvider = $feePlanProvider;
	}

	/**
	 * Load the only backend gateway to configure it
	 * Load only if the user is in the admin area and on the Gateway settings page
	 * @return void
	 */
	public function loadBackendGateway(): void {
		if ( ContextHelper::isAdmin() ) {
			if ( ContextHelper::isGatewaySettingsPage() ) {
				$this->backendHelper->loadBackendGateway();
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
				$this->backendHelper->loadBackendGateway();
			} else {
				if ( PluginHelper::isConfigured() ) {
					$this->frontendHelper->loadFrontendGateways();
				}
			}
			// Add links to gateway.
			$this->gatewayHelper->addGatewayLinks(
				PluginHelper::getPluginFile(),
				array( $this, 'pluginActionLinks' )
			);

		} else {
			if ( PluginHelper::isConfigured() ) {
				$this->frontendHelper->loadFrontendGateways();
			}
		}

		if ( PluginHelper::isConfigured() ) {
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
	 * Configure each gateway with eligibility and fee plans
	 * @throws GatewayServiceException|FeePlanRepositoryException
	 */
	public function configureGateway() {

		if ( ! $this->eligibilityProvider || ! $this->feePlanProvider ) {
			$logger = Plugin::get_container()->get( LoggerService::class );
			$logger->debug( 'configure_gateway : Errors in services eligibility or fee plan' );

			return;
		}

		/** @var FeePlanRepository $feePlanRepository */
		$feePlanRepository = Plugin::get_container()->get( FeePlanRepository::class );

		try {
			/** @var AbstractGateway $gateway */
			foreach ( $this->gatewayRepository->findAllAlmaGateways() as $gateway ) {
				if ( ContextHelper::isCheckoutPage() ) {
					$gateway->configure_eligibility( $this->eligibilityProvider->getEligibilityList(
						( new EligibilityMapper() )
							->buildEligibilityDto( ContextHelper::getCart(), ContextHelper::getCustomer() )
					) );
					$gateway->configure_fee_plans( $feePlanRepository->getAll() );
				}
			}
		} catch ( EligibilityServiceException $e ) {
			throw new GatewayServiceException( $e->getMessage() );
		}
	}


	/**
	 * Configure returns (ipn and customer_return ) if configuration is done
	 *
	 * @return void
	 */
	public function configureReturns() {
		$this->ipnHelper->configureIpnCallback();
		$this->ipnHelper->configureCustomerReturn();
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

		if ( $this->configService->isBlocksEnabled() ) {

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
