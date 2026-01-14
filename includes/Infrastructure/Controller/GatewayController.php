<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Controller\GatewayControllerException;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Infrastructure\Service\GatewayService;
use Alma\Gateway\Plugin;

class GatewayController {

	private GatewayService $gatewayService;
	private AssetsService $assetsService;
	private BusinessEventsService $businessEventsService;
	private GatewayHelper $gatewayHelper;
	private GatewayRepository $gatewayRepository;

	public function __construct(
		GatewayService $gatewayService,
		AssetsService $assetsService,
		BusinessEventsService $businessEventsService,
		GatewayRepository $gatewayRepository,
		GatewayHelper $gatewayHelper
	) {
		$this->gatewayService    = $gatewayService;
		$this->assetsService     = $assetsService;
		$this->businessEventsService = $businessEventsService;
		$this->gatewayRepository = $gatewayRepository;
		$this->gatewayHelper     = $gatewayHelper;
	}

	/**
	 * Prepare services.
	 */
	public function prepare() {

		$this->gatewayService->configureReturns();
		almaLogConsole( '1 - PREPARE - Configure Returns' );

		// Register Gateway Block
		if ( ContextHelper::isCheckoutPageUseBlocks() ) {
			$this->gatewayService->initGatewayBlocks();
			almaLogConsole( '1 - PREPARE - Register Gateway Block' );
		}
	}

	/**
	 * Run services.
	 * @throws GatewayControllerException
	 */
	public function run() {

		// Init Gateway Services
		$this->loadGateway();
		try {
			$this->gatewayService->runGatewayBlocks();
			almaLogConsole( '2 - RUN - Register Gateway Block Assets' );
		} catch ( GatewayServiceException $e ) {
			throw new GatewayControllerException();
		}
	}

	/**
	 * Load the admin gateway to do configuration.
	 * Load only in admin area on gateway settings page
	 */
	public function configure() {
		if ( ContextHelper::isAdmin() ) {
			if ( ContextHelper::isGatewaySettingsPage() ) {
				BackendHelper::loadBackendGateway();
				almaLogConsole( '0 - CONFIGURE - Load Backend Gateway' );
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
	 * @throws GatewayControllerException
	 */
	private function loadGateway() {
		// Init Gateway
		if ( ContextHelper::isAdmin() ) {
			if ( ContextHelper::isGatewaySettingsPage() ) {
				BackendHelper::loadBackendGateway();
				almaLogConsole( '2 - RUN - Load Backend Gateways' );
			} else {
				FrontendHelper::loadFrontendGateways( $this->gatewayRepository->findOrderedAlmaGateways() );
				almaLogConsole( '2 - RUN - Load Gateways' );
			}
			// Add links to gateway.
			$this->gatewayHelper->addGatewayLinks(
				Plugin::get_instance()->get_plugin_file(),
				array( $this->gatewayService, 'pluginActionLinks' )
			);
		} else {
			try {
				$this->assetsService->registerClassicCheckoutAssets();
				almaLogConsole( '2 - RUN - Register Classic Checkout Assets' );
				$this->assetsService->displayClassicCheckoutAssets();
				almaLogConsole( '3 - DISPLAY - Load Classic Checkout Assets' );
			} catch ( AssetsServiceException $e ) {
				throw new GatewayControllerException();
			}

			FrontendHelper::loadFrontendGateways( $this->gatewayRepository->findOrderedAlmaGateways() );
			almaLogConsole( '2 - RUN - Load Frontend Gateways' );

			EventHelper::addEvent(
				'woocommerce_add_to_cart',
				array( Plugin::get_container()->get( BusinessEventsService::class ), 'onCartInitiated' )
			);
		}

		// For Business event on create order to set the order id on classic checkout
		EventHelper::addEvent(
			'woocommerce_checkout_update_order_meta',
			array( $this->businessEventsService, 'onCreateOrder' ),
			10,
			1
		);

		// For Business event on create order to set the order id on Block checkout
		EventHelper::addEvent(
			'woocommerce_store_api_checkout_update_order_meta',
			array( $this->businessEventsService, 'onCreateOrderBlock' ),
			10,
			1
		);

		// Configure the hooks linked to the gateways
		EventHelper::addEvent(
			'woocommerce_order_status_changed',
			array( $this->gatewayService, 'woocommerceOrderStatusChanged' ),
			10,
			3
		);
	}
}
