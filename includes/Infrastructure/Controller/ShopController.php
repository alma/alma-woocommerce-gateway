<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\InPageService;
use Alma\Gateway\Application\Service\WidgetService;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\BlocksWidgetHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Plugin;

class ShopController {

	/**
	 * Register widgets on warm up
	 * @return void
	 */
	public function warm() {
		if ( ContextHelper::isCartPageUseBlocks() ) {
			BlocksWidgetHelper::registerWidget();
		}
	}

	/**
	 * Run on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 */
	public function run() {
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
