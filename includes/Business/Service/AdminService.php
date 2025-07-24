<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Plugin;

class AdminService {

	public const ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE = 'woocommerce-alma-toggle-fee-plan-enabled';


	/**
	 * AdminService constructor.
	 */
	public function __construct() {
		$this->register_ajax_handlers();
	}

	/**
	 * Enqueue admin scripts.
	 * @return void
	 * @todo move to proxy
	 */
	public function register_ajax_handlers() {
		add_action( 'wp_ajax_alma_toggle_fee_plan_enabled', array( $this, 'toggle_alma_fee_plan_enabled' ) );
	}

	/**
	 * Toggle Alma Fee Plan enabled state.
	 * This function is called via AJAX to enable or disable an Alma Fee Plan.
	 * @return void
	 * @throws ContainerException
	 */
	public function toggle_alma_fee_plan_enabled() {
		// Check nonce for security and rights
		check_ajax_referer( self::ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE, 'security' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( - 1 );
		}
		// Toggle the gateway enabled state
		$fee_plan_id = isset( $_POST['fee_plan_key'] ) ? sanitize_text_field( $_POST['fee_plan_key'] ) : '';
		/** @var OptionsService $option_service */
		$option_service = Plugin::get_container()->get( OptionsService::class );
		$new_status     = $option_service->toggle_fee_plan( $fee_plan_id );

		wp_send_json_success( true === $new_status );
	}

	/**
	 * Enqueue admin scripts.
	 * This function is called to enqueue the admin scripts for the Alma gateway.
	 * @return void
	 * @throws ContainerException
	 */
	public function enqueue_admin_scripts() {
		/** @var AssetsHelper $assert_helper */
		$assert_helper = Plugin::get_container()->get( AssetsHelper::class );

		wp_enqueue_script(
			'alma-admin-js',
			$assert_helper->get_asset_url( 'js/backend/alma-admin.js' ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'alma-admin-js',
			'alma_settings',
			array(
				'nonce'    => wp_create_nonce( self::ALMA_TOGGLE_FEE_PLAN_ENABLED_NONCE ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}
}
