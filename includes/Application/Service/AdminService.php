<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Domain\Helper\EventHelperInterface;
use Alma\API\Domain\Helper\SecurityHelperInterface;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Plugin;

class AdminService {

	public const ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE = 'woocommerce-alma-toggle-fee-plan-enabled';

	/** @var EventHelperInterface */
	private EventHelperInterface $eventHelper;

	/** @var SecurityHelperInterface */
	private SecurityHelperInterface $securityHelper;

	/** @var ConfigService */
	private ConfigService $configService;

	/** @var AssetsHelper */
	private AssetsHelper $assetsHelper;

	/** @var ContextHelperInterface */
	private ContextHelperInterface $contextHelper;

	/**
	 * AdminService constructor.
	 */
	public function __construct(
		EventHelperInterface $eventHelper,
		SecurityHelperInterface $securityHelper,
		ContextHelperInterface $contextHelper,
		ConfigService $configService,
		AssetsHelper $assetsHelper
	) {
		$this->eventHelper    = $eventHelper;
		$this->securityHelper = $securityHelper;
		$this->contextHelper  = $contextHelper;
		$this->configService  = $configService;
		$this->assetsHelper   = $assetsHelper;
	}

	/**
	 * Run services on admin init.
	 */
	public static function runService() {
		BackendHelper::runBackendServices(
			function () {
				// Init Backend Services
				if ( ContextHelper::isAdmin() ) {
					/** @var AdminService $admin_service */
					$admin_service = Plugin::get_container()->get( AdminService::class );
					$admin_service->enqueueAdminScripts();
				}
			}
		);
	}

	/**
	 * Enqueue admin scripts.
	 * This function is called to enqueue the admin scripts for the Alma gateway.
	 *
	 * @return void
	 */
	public function enqueueAdminScripts() {
		AssetsHelper::enqueueAdminScript( '1.0.0' );
	}
}
