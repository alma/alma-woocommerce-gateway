<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Adapter\UserAdapterInterface;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Domain\Helper\EventHelperInterface;
use Alma\API\Domain\Helper\SecurityHelperInterface;
use Alma\Gateway\Application\Helper\AdminHelper;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\BackendHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\ParameterHelper;
use Alma\Gateway\Infrastructure\Repository\UserRepository;
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
		$this->registerAjaxHandlers();
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
	 *
	 * @return void
	 */
	public function registerAjaxHandlers() {
		EventHelper::addEvent( 'wp_ajax_alma_toggle_fee_plan_enabled',
			array( $this, 'toggleAlmaFeePlanEnabled' ) );
	}

	/**
	 * Toggle Alma Fee Plan enabled state.
	 * This function is called via AJAX to enable or disable an Alma Fee Plan.
	 *
	 * @return void
	 * @throws ContainerServiceException
	 */
	public function toggleAlmaFeePlanEnabled() {
		// Check nonce for security and rights, die if not valid
		$this->securityHelper->validateAjaxToken( self::ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE );

		$userRepository = Plugin::get_container()->get( UserRepository::class );
		/** @var UserAdapterInterface $currentUser */
		$currentUser = $userRepository->getById( ContextHelper::getCurrentUserId() );
		if ( ! $currentUser->canManageAlma() ) {
			AdminHelper::canManageAlmaError();
		}
		// Toggle the gateway enabled state
		$feePlanId = isset( $_POST['fee_plan_key'] ) ? ParameterHelper::checkAndCleanParam( $_POST['fee_plan_key'] ) : '';
		$newStatus = $this->configService->toggleFeePlan( $feePlanId );

		AdminHelper::success( $newStatus );
	}

	/**
	 * Enqueue admin scripts.
	 * This function is called to enqueue the admin scripts for the Alma gateway.
	 *
	 * @return void
	 */
	public function enqueueAdminScripts() {
		AssetsHelper::enqueueAdminScript( '1.0.0' );

		AssetsHelper::configureAdminScript( array(
			'nonce'    => $this->securityHelper->generateToken( self::ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE ),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );


	}
}
