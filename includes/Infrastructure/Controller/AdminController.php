<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;

class AdminController {

	/** @var AssetsService */
	private AssetsService $assetsService;

	/**
	 * AdminController constructor.
	 */
	public function __construct(
		AssetsService $assetsService
	) {
		$this->assetsService = $assetsService;
	}

	/**
	 * Display services on admin init.
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
				}
			}
		);
	}
}
