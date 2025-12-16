<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\CheckoutServiceException;
use Alma\Gateway\Infrastructure\Exception\Controller\AssetsControllerException;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Infrastructure\Service\CheckoutService;
use Alma\Gateway\Infrastructure\Service\GatewayService;
use Alma\Gateway\Plugin;

class GatewayController {

	private GatewayService $gatewayService;
	private AssetsService $assetsService;
	private GatewayHelper $gatewayHelper;

	public function __construct(
		GatewayService $gatewayService,
		AssetsService $assetsService,
		GatewayHelper $gatewayHelper
	) {
		$this->gatewayService = $gatewayService;
		$this->assetsService  = $assetsService;
		$this->gatewayHelper  = $gatewayHelper;
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
	 * @throws AssetsControllerException
	 */
	public function run() {

		// Init Gateway Services
		$this->loadGateway();

		/** @var GatewayRepository $gatewayRepository */
		$gatewayRepository = Plugin::get_container()->get( GatewayRepository::class );
		$almaGatewayBlocks = $gatewayRepository->findAllAlmaGatewayBlocks();
		try {
			/** @var CheckoutService $checkoutService */
			$checkoutService        = Plugin::get_container()->get( CheckoutService::class );
			$params                 = $checkoutService->getCheckoutParams( $almaGatewayBlocks );
			$params['checkout_url'] = ContextHelper::getWebhookUrl( 'alma_checkout_data' );
			$this->assetsService->registerGatewayBlockAssets( $params );
			almaLogConsole( '2 - RUN - Register Gateway Block Assets' );
		} catch ( CheckoutServiceException|AssetsServiceException $e ) {
			throw new AssetsControllerException( 'Unable to load block assets', 0, $e );
		}
	}

	/**
	 * Display the service by loading assets
	 *
	 * @return void
	 */
	public function display() {
		if ( ! Plugin::get_instance()->is_configured() ) {
			return;
		}

		FrontendHelper::displayFrontendServices(
			function () {
				if ( ContextHelper::isAdmin() || ( ContextHelper::isCheckoutPage() && ContextHelper::isCheckoutPageUseBlocks() ) ) {
					$this->assetsService->displayGatewayBlockAssets();
					almaLogConsole( '3 - DISPLAY - Display Gateway Block Assets' );
				}
			}
		);
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
	 */
	private function loadGateway() {
		// Init Gateway
		if ( ContextHelper::isAdmin() ) {
			if ( ContextHelper::isGatewaySettingsPage() ) {
				BackendHelper::loadBackendGateway();
				almaLogConsole( '2 - RUN - Load Backend Gateways' );
			} else {
				FrontendHelper::loadFrontendGateways();
				almaLogConsole( '2 - RUN - Load Gateways' );
			}
			// Add links to gateway.
			$this->gatewayHelper->addGatewayLinks(
				Plugin::get_instance()->get_plugin_file(),
				array( $this->gatewayService, 'pluginActionLinks' )
			);
		} else {
			FrontendHelper::loadFrontendGateways();
			almaLogConsole( '2 - RUN - Load Frontend Gateways' );
		}

		// Configure the hooks linked to the gateways
		EventHelper::addEvent(
			'woocommerce_order_status_changed',
			array( $this->gatewayService, 'woocommerceOrderStatusChanged' ),
			10,
			3
		);
	}
}
