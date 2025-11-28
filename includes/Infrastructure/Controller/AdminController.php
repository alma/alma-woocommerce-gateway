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
	 * Run services on admin init.
	 */
	public function run() {
		BackendHelper::runBackendServices(
			function () {
				// Init Backend Services
				if ( ContextHelper::isAdmin() ) {
					// Load Admin Assets
					$this->assetsService->loadAdminAssets();
				}
			}
		);
	}
}
