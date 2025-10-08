<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;

class AdminService {

	/** @var AssetsService */
	private AssetsService $assetsService;

	/**
	 * AdminService constructor.
	 */
	public function __construct(
		AssetsService $assetsService
	) {
		$this->assetsService = $assetsService;
	}

	/**
	 * Run services on admin init.
	 */
	public static function runService() {
		BackendHelper::runBackendServices(
			function () {
				// Init Backend Services
				if ( ContextHelper::isAdmin() ) {
					$this->assetsService->loadAdminAssets();
				}
			}
		);
	}
}
