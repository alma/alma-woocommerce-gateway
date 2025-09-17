<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Exception\Service\API\FeePlanServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\API\EligibilityService;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Repository\UserRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Plugin;

class GatewayService {

	/** GatewayHelper */
	private GatewayHelper $gatewayHelper;

	/** @var EligibilityService|null The Eligibility Service if available */
	private ?EligibilityService $eligibilityService = null;

	/** @var FeePlanService|null The Fee plan Service if available */
	private ?FeePlanService $feePlanService = null;

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

	public function __construct(
		GatewayRepository $gatewayRepository,
		GatewayHelper $gatewayHelper,// Move
		IpnHelper $ipnHelper,
		ContextHelperInterface $contextHelper,
		FrontendHelper $frontendHelper,
		BackendHelper $backendHelper
	) {
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
	 * @param EligibilityService $eligibilityService
	 *
	 * @return void
	 */
	public function setEligibilityService( EligibilityService $eligibilityService ): void {
		$this->eligibilityService = $eligibilityService;
	}

	/**
	 * Set the fee plan service. This can't be done in the constructor because the service
	 * could be not available at the time if the plugin in not fully configured.
	 *
	 * @param FeePlanService $feePlanService
	 *
	 * @return void
	 */
	public function setFeePlanService( FeePlanService $feePlanService ): void {
		$this->feePlanService = $feePlanService;
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
				$this->frontendHelper->loadFrontendGateways();
			}
			// Add links to gateway.
			$this->gatewayHelper->addGatewayLinks(
				PluginHelper::getPluginFile(),
				array( $this, 'pluginActionLinks' )
			);
		} else {
			$this->frontendHelper->loadFrontendGateways();
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
	 * @throws GatewayServiceException|ContainerServiceException
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

				/** @var PaymentService $paymentService */
				$paymentService = Plugin::get_container()->get( PaymentService::class );

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
	 * @throws ContainerServiceException|GatewayServiceException
	 */
	public function configureGateway() {

		if ( ! $this->eligibilityService || ! $this->feePlanService ) {
			$logger = Plugin::get_container()->get( LoggerService::class );
			$logger->debug( 'configure_gateway : Errors in services eligibility or fee plan' );

			return;
		}

		try {
			/** @var AbstractGateway $gateway */
			foreach ( $this->gatewayRepository->getAlmaGateways() as $gateway ) {
				if ( ContextHelper::isCheckoutPage() ) {
					$gateway->configure_eligibility( $this->eligibilityService->getEligibilityList() );
					$gateway->configure_fee_plans( $this->feePlanService->getFeePlanList() );
				}
			}
		} catch ( EligibilityServiceException|FeePlanServiceException $e ) {
			throw new GatewayServiceException( $e->getMessage() );
		}
	}


	/**
	 * Configure returns (ipn and customer_return ) if configuration is done
	 *
	 * @return void
	 * @throws ContainerServiceException
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
		$setting_link = ContextHelper::adminUrl( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' );
		$plugin_links = array(
			sprintf( '<a href="%s">%s</a>', $setting_link, L10nHelper::__( 'Settings' ) ),
		);

		return array_merge( $plugin_links, $links );
	}
}
