<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Application\Service\PluginService;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Controller\AdminControllerException;
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
	 * Prepare services.
	 * @return void
	 * @throws AdminControllerException
	 */
	public function prepare() {
		// Add Admin Notifications
		$this->pluginService->addAlmaAdminNotifications();
		$this->pluginService->addAlmaLinksOnAdmin();

		// Register Admin Assets
		try {
			$this->assetsService->registerAdminAssets();
			$this->assetsService->registerWidgetAssets();
			$this->assetsService->registerWidgetBlockEditorAssets();
		} catch ( AssetsServiceException $e ) {
			throw new AdminControllerException( $e->getMessage() );
		}
	}

	/**
	 * Display services on admin init
	 */
	public function display() {
		BackendHelper::runBackendServices(
			function () {
				// Init Backend Services
				if ( ContextHelper::isAdmin() ) {

					// Display Admin Assets
					$this->assetsService->displayAdminAssets();
					$this->assetsService->displayWidgetAssets();
				}
			}
		);
	}
}
