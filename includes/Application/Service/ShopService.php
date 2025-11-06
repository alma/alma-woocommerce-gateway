<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Exception\Service\AdminServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Service\GatewayService;
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
					throw new AdminServiceException( $e->getMessage() );
				}

				/** @var WidgetService $widget_service */
				$widget_service = Plugin::get_container()->get( WidgetService::class );
				$widget_service->displayWidget();

				/** @var ConfigService $configService */
				$configService = Plugin::get_container()->get( ConfigService::class );

				// Enabled In-Page on product or shop page
				if ( $configService->isInPageEnabled() ) {
					/** @var InPageService $inPageService */
					$inPageService = Plugin::get_container()->get( InPageService::class );
					$inPageService->displayInPage();
				}

			}
		);
	}
}
