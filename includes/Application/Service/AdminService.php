<?php

namespace Alma\Gateway\Application\Service;

use Alma\API\Domain\Exception\ContainerException;
use Alma\API\Domain\Helper\EventHelperInterface;
use Alma\API\Domain\Helper\SecurityHelperInterface;
use Alma\Gateway\Application\Helper\AssetsHelper;

class AdminService {

	public const ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE = 'woocommerce-alma-toggle-fee-plan-enabled';

	/** @var EventHelperInterface */
	private EventHelperInterface $eventDispatcher;

	/** @var SecurityHelperInterface */
	private SecurityHelperInterface $securityHelper;

	/** @var ConfigService */
	private ConfigService $configService;

	/** @var AssetsHelper */
	private AssetsHelper $assetsHelper;

	/**
	 * AdminService constructor.
	 */
	public function __construct(
		EventHelperInterface $eventDispatcher,
		SecurityHelperInterface $securityHelper,
		ConfigService $configService,
		AssetsHelper $assetsHelper
	) {
		$this->eventDispatcher = $eventDispatcher;
		$this->securityHelper  = $securityHelper;
		$this->configService   = $configService;
		$this->assetsHelper    = $assetsHelper;
		$this->registerAjaxHandlers();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @return void
	 */
	public function registerAjaxHandlers() {
		$this->eventDispatcher->addEvent( 'wp_ajax_alma_toggle_fee_plan_enabled',
			array( $this, 'toggleAlmaFeePlanEnabled' ) );
	}

	/**
	 * Toggle Alma Fee Plan enabled state.
	 * This function is called via AJAX to enable or disable an Alma Fee Plan.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public function toggleAlmaFeePlanEnabled() {
		// Check nonce for security and rights
		check_ajax_referer( self::ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE, 'security' ); // @todo use a proxy
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // @todo use a proxy
			wp_die( - 1 ); // @todo use a proxy
		}
		// Toggle the gateway enabled state
		$feePlanId = isset( $_POST['fee_plan_key'] ) ? sanitize_text_field( $_POST['fee_plan_key'] ) : ''; // @todo use a proxy
		$newStatus = $this->configService->toggleFeePlan( $feePlanId );

		wp_send_json_success( true === $newStatus ); // @todo use a proxy
	}

	/**
	 * Enqueue admin scripts.
	 * This function is called to enqueue the admin scripts for the Alma gateway.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public function enqueueAdminScripts() {
		wp_enqueue_script( // @todo use a proxy
			'alma-admin-js',
			$this->assetsHelper->get_asset_url( 'js/backend/alma-admin.js' ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script( // @todo use a proxy
			'alma-admin-js',
			'alma_settings',
			array(
				'nonce'    => $this->securityHelper->generateToken( self::ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE ),
				'ajax_url' => admin_url( 'admin-ajax.php' ), // @todo use a proxy
			)
		);
	}
}
