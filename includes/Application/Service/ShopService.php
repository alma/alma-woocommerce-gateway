<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\BlocksWidgetHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Plugin;

class ShopService {

	/**
	 * Register widgets on warm up
	 * @return void
	 */
	public function warmService() {
		if ( ContextHelper::isCartPageUseBlocks() ) {
			BlocksWidgetHelper::registerWidget();
		}
	}

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

		BackendHelper::runBackendServices(
			function () {
				if ( ! PluginHelper::isConfigured() ) {
					return;
				}

				/** @var WidgetService $widget_service */
				$widget_service = Plugin::get_container()->get( WidgetService::class );
				$widget_service->displayWidget();
			}
		);
	}
}
