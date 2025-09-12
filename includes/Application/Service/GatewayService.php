<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Domain\Helper\EventHelperInterface;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\API\EligibilityService;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Plugin;

class GatewayService {

	public const CUSTOMER_RETURN = 'alma_customer_return';
	public const IPN_CALLBACK = 'alma_ipn_callback';

	/** GatewayHelper */
	private GatewayHelper $gatewayHelper;

	/** @var EligibilityService|null The Eligibility Service if available */
	private ?EligibilityService $eligibilityService = null;

	/** @var FeePlanService|null The Fee plan Service if available */
	private ?FeePlanService $feePlanService = null;

	/** @var EventHelperInterface The Event Adapter */
	private EventHelperInterface $eventHelper;

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
		EventHelperInterface $eventHelper,
		ContextHelperInterface $contextHelper,
		FrontendHelper $frontendHelper,
		BackendHelper $backendHelper
	) {
		$this->gatewayRepository = $gatewayRepository;
		$this->gatewayHelper     = $gatewayHelper;
		$this->eventHelper       = $eventHelper;
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
		if ( $this->contextHelper->isAdmin() ) {
			if ( $this->contextHelper->isGatewaySettingsPage() ) {
				$this->backendHelper->loadBackendGateway();
			} else {
				$this->frontendHelper->loadFrontendGateways();
			}
			// Add links to gateway.
			$this->gatewayHelper->addGatewayLinks(
				Plugin::get_instance()->get_plugin_file(),
				array( $this, 'pluginActionLinks' )
			);
		} else {
			$this->frontendHelper->loadFrontendGateways();
		}

		// Configure the hooks linked to the gateways
		$this->eventHelper->addEvent( 'woocommerce_order_status_changed',
			array( $this, 'woocommerceOrderStatusChanged' ), 10, 3 );
	}

	/**
	 * Callback function for the event "order status changed".
	 * This method will make full refund if possible.
	 *
	 * @param int    $order_id Order id.
	 * @param string $old_status Old status (not used but necessary for the callback).
	 * @param string $new_status New status.
	 *
	 * @sonar We need to keep $old_status on the signature for the hook
	 *
	 * @throws GatewayServiceException|ContainerServiceException
	 */
	public function woocommerceOrderStatusChanged(
		int $order_id,
		string $old_status,// NOSONAR -- We need to keep this signature for the hook
		string $new_status
	): void {

		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		try {
			$order = $order_repository->findById( $order_id );
		} catch ( ProductRepositoryException $e ) {
			throw new GatewayServiceException( 'Order not found' );
		}

		if ( 'refunded' === $new_status || 'cancelled' === $new_status ) {
			if ( $order->isRefundable() ) {

				/** @var PaymentService $payment_service */
				$payment_service = Plugin::get_container()->get( PaymentService::class );

				try {
					$payment_service->refundPayment(
						$order->getPaymentId(),
						( new RefundDto() )
							->setAmount( DisplayHelper::price_to_cent( $order->getRemainingRefundAmount() ) )
							->setMerchantReference( $order->getOrderNumber() )
							->setComment( L10nHelper::__( 'Full refund requested by the merchant' ) )
					);
				} catch ( ParametersException $e ) {
					throw new GatewayServiceException( $e->getMessage() );
				}

				$order->addOrderNote(
					sprintf(
						L10nHelper::__( 'Order fully refunded by %s.' ),
						wp_get_current_user()->display_name
					)
				);
			}
		}
	}


	/**
	 * Configure each gateway with eligibility and fee plans
	 */
	public function configureGateway() {

		if ( ! $this->eligibilityService || ! $this->feePlanService ) {
			$logger = Plugin::get_container()->get( LoggerService::class );
			$logger->debug( 'configure_gateway : Errors in services eligibility or fee plan' );

			return;
		}

		/** @var AbstractGateway $gateway */
		foreach ( $this->gatewayRepository->getAlmaGateways() as $gateway ) {
			if ( $this->contextHelper->isCheckoutPage() ) {
				$gateway->configure_eligibility( $this->eligibilityService->getEligibilityList() );
				$gateway->configure_fee_plans( $this->feePlanService->getFeePlanList() );
			}
		}
	}


	/**
	 * Configure returns (ipn and customer_return ) if configuration is done
	 *
	 * @return void
	 * @throws ContainerServiceException
	 */
	public function configureReturns() {
		$this->eventHelper->addEvent(
			'woocommerce_api_' . self::IPN_CALLBACK,
			array( Plugin::get_container()->get( IpnService::class ), 'handleIpnCallback' )
		);
		$this->eventHelper->addEvent(
			'woocommerce_api_' . self::CUSTOMER_RETURN,
			array( Plugin::get_container()->get( IpnService::class ), 'handleCustomerReturn' )
		);
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
