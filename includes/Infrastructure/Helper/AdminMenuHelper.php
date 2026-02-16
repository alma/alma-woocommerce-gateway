<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Plugin;

class AdminMenuHelper {

	/**
	 * Add Alma top level menu in WP admin.
	 */
	public static function almaAddGatewayTopMenu() {
		add_menu_page(
			__( 'Alma - Settings', 'alma-gateway-for-woocommerce' ),
			__( 'Alma', 'alma-gateway-for-woocommerce' ),
			'manage_options',
			'alma-gateway-settings',
			[ NavigationHelper::class, 'alma_redirect_to_gateway_settings' ],
			AssetsHelper::getAssetUrl( 'images/alma_short_logo.svg' ),
			54
		);
	}

	public static function addGatewayLinks() {
		add_filter(
			'plugin_action_links_' . plugin_basename( Plugin::get_instance()->get_plugin_file() ),
			[ AdminMenuHelper::class, 'pluginActionLinks' ]
		);
	}

	/**
	 * Add links to gateway.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public static function pluginActionLinks( $links ): array {
		$setting_link = ContextHelper::getAdminUrl( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' );
		$plugin_links = array(
			sprintf( '<a href="%s">%s</a>', $setting_link,
				__( 'Settings', 'alma-gateway-for-woocommerce' ) ),
		);

		return array_merge( $plugin_links, $links );
	}
}
