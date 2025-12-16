<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\InPageService;
use Alma\Gateway\Application\Service\WidgetService;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\BlocksWidgetHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\FrontendHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Plugin;

class ShopController {

	private ConfigService $configService;
	private AssetsService $assetsService;
	private InPageService $inPageService;
	private WidgetService $widgetService;

	public function __construct(
		ConfigService $configService,
		AssetsService $assetsService,
		InPageService $inPageService,
		WidgetService $widgetService
	) {
		$this->configService = $configService;
		$this->assetsService = $assetsService;
		$this->inPageService = $inPageService;
		$this->widgetService = $widgetService;
	}

	/**
	 * Register widgets on warm up
	 * @return void
	 */
	public function prepare() {
		if ( ContextHelper::isCartPageUseBlocks() ) {
			BlocksWidgetHelper::registerWidget();
			almaLogConsole( '1 - PREPARE - Register Widget Block' );
		}
	}

	/**
	 * Run on template redirect.
	 * We need to wait templates because is_page detection run at this time!
	 */
	public function run() {
		FrontendHelper::runFrontendServices(
			function () {

				if ( ! Plugin::get_instance()->is_plugin_needed() ) {
					return;
				}

				BlocksWidgetHelper::prepareWidgetAssets();
				almaLogConsole( '2 - RUN - Register Widget Blocks Assets' );

				$this->widgetService->runWidget();
				almaLogConsole( '2 - RUN - Run Widget' );

				// Enabled In-Page
				if ( ContextHelper::isCheckoutPage() && $this->configService->isInPageEnabled() ) {
					$this->inPageService->runInPage();
					almaLogConsole( '2 - RUN - Run In-Page' );
				}
			}
		);

		BackendHelper::runBackendServices(
			function () {
				if ( ! Plugin::get_instance()->is_configured() ) {
					return;
				}

				$this->widgetService->runWidget();
				almaLogConsole( '2 - RUN - Widget for backend' );
			}
		);
	}

	/**
	 * Display widgets on warm up
	 * @return void
	 */
	public function display() {
		FrontendHelper::displayFrontendServices(
			function () {
				if ( ContextHelper::isCartPage() || ContextHelper::isProductPage() ) {
					$this->assetsService->displayWidgetAssets();
					almaLogConsole( '3 - DISPLAY - Load Widget Assets' );
				}

				if ( ContextHelper::isCheckoutPage() && $this->configService->isInPageEnabled() ) {
					$this->assetsService->displayInPageAssets();
					almaLogConsole( '3 - DISPLAY - Load In-Page Assets' );
				}
			}
		);
	}
}
