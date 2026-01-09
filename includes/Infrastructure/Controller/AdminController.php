<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Application\Service\PluginService;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;

class AdminController {

	/** @var AssetsService */
	private AssetsService $assetsService;

	/** @var PluginService */
	private PluginService $pluginService;

	/**
	 * AdminController constructor.
	 */
	public function __construct(
		AssetsService $assetsService,
		PluginService $pluginService
	) {
		$this->assetsService = $assetsService;
		$this->pluginService = $pluginService;
	}

	/**
	 * Display services on admin init
	 */
	public function display() {
		BackendHelper::runBackendServices(
			function () {
				// Init Backend Services
				if ( ContextHelper::isAdmin() ) {
					// Register Admin Assets
					$this->assetsService->registerAdminAssets();
					almaLogConsole( '2 - RUN - Register Admin Assets' );
					$this->assetsService->registerWidgetAssets();
					almaLogConsole( '2 - RUN - Register Widget Assets' );
					$this->assetsService->registerWidgetBlockEditorAssets();
					almaLogConsole( '2 - RUN - Register Block Widget Editor Assets' );
					// Display Admin Assets
					$this->assetsService->displayAdminAssets();
					almaLogConsole( '3 - Display - Load Admin Assets' );
					$this->assetsService->displayWidgetAssets();
					almaLogConsole( '3 - Display - Load Widget Assets' );

					// Add Admin Notifications
					$this->pluginService->addAlmaAdminNotifications();
				}
			}
		);
	}
}
