<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Application\Exception\Controller\GatewayControllerException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Helper\GatewayHelper;
use Alma\Gateway\Infrastructure\Service\GatewayService;

class GatewayController {

	private GatewayService $gatewayService;
	private GatewayHelper $gatewayHelper;

	public function __construct(
		GatewayService $gatewayService,
		GatewayHelper $gatewayHelper
	) {
		$this->gatewayService = $gatewayService;
		$this->gatewayHelper  = $gatewayHelper;
	}

	/**
	 * Run services on admin init.
	 * @throws GatewayControllerException
	 */
	public function run() {

		// Init Gateway Services
		$this->loadGateway();
		$this->gatewayService->configureReturns();
	}

	/**
	 * Load the admin gateway to do configuration.
	 * Load only in admin area on gateway settings page
	 */
	public function configure() {
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
	 * @throws GatewayControllerException
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
				array( $this->gatewayService, 'pluginActionLinks' )
			);
		} else {
			FrontendHelper::loadFrontendGateways();
		}

		if ( ContextHelper::isCheckoutPageUseBlocks() ) {
			try {
				$this->gatewayService->initGatewayBlocks();
			} catch ( GatewayServiceException $e ) {
				throw new GatewayControllerException();
			}
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
