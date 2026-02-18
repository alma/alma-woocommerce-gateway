<?php

namespace Alma\Gateway\Application\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Helper\AdminMenuHelper;
use Alma\Gateway\Infrastructure\Helper\AdminNotificationHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;

/**
 * Everything related to the plugin service.
 * Notifications...
 */
class PluginService {

	/** @var ConfigService */
	private ConfigService $configService;

	public function __construct(
		ConfigService $configService
	) {
		$this->configService = $configService;
	}

	/**
	 * Add admin notifications related to Alma plugin.
	 * (and remove notifications from other plugins if on Alma settings page)
	 * @return void
	 */
	public function addAlmaAdminNotifications(): void {
		EventHelper::addEvent( 'admin_head', function () {
			if ( ContextHelper::isGatewaySettingsPage( true ) ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
			}
			$this->remindToEnableAlma();
			$this->remindToConfigureAlma();
		} );
	}

	/**
	 * Remind the admin to enable the plugin if not done yet.
	 * @return void
	 */
	public function remindToEnableAlma(): void {
		if ( ! $this->configService->isEnabled() ) {
			AdminNotificationHelper::notifyInfo(
				sprintf(
				// translators: %s: url for activating alma
					__( 'Thanks for installing Alma! Start by <a href="%s">activating Alma\'s payment method</a>, then set it up to get started.',
						'alma-gateway-for-woocommerce' ),
					ContextHelper::getAdminUrl( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' )
				)
			);
		}
	}

	/**
	 * Remind the admin to configure the plugin if not done yet.
	 * @return void
	 */
	public function remindToConfigureAlma(): void {
		if ( ! $this->configService->hasKeys() ) {
			AdminNotificationHelper::notifyInfo(
				sprintf(
				// translators: %s: The url of the config page
					__( 'Alma is almost ready. To get started, <a href="%s">fill in your API keys</a>.',
						'alma-gateway-for-woocommerce' ),
					ContextHelper::getAdminUrl( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' )
				)
			);
		}
	}

	/**
	 * Add Alma links on WordPress admin menu.
	 *
	 * @return void
	 */
	public function addAlmaLinksOnAdmin() {

		AdminMenuHelper::almaAddGatewayTopMenu();
		AdminMenuHelper::addGatewayLinks();
	}
}
