<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Exception\Service\AdminServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Plugin;

class ShopService {

	/**
	 * Run services on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 */
	public function runService() {
		FrontendHelper::runFrontendServices(
			function () {
				if ( ! PluginHelper::isPluginNeeded() ) {
					return;
				}

				/** @var GatewayService $gatewayService */
				$gatewayService = Plugin::get_container()->get( GatewayService::class );
				try {
					$gatewayService->configureGateway();
				} catch ( GatewayServiceException $e ) {
					throw new AdminServiceException();
				}

				if ( PluginHelper::isConfigured() ) {
					/** @var WidgetService $widgetService */
					$widgetService = Plugin::get_container()->get( WidgetService::class );
					$widgetService->displayWidget();
				}
			}
		);
	}
}
