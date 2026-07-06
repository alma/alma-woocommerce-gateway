<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Service\CollectCmsDataService;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Backend\AlmaGateway;
use Alma\Gateway\Plugin;

class CollectCmsDataHelper {

	/**
	 * Returns the 1-based position of the Alma config gateway
	 * among all payment gateways (excluding Alma frontend gateways).
	 * Reads from the persisted WooCommerce gateway order option so this works
	 * regardless of which gateways are currently loaded in memory.
	 * Returns 0 if the Alma config gateway is not found in the stored order.
	 */
	public function getPaymentMethodPosition(): int {
		$ordering = get_option( 'woocommerce_gateway_order', array() );
		$configId = sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, AlmaGateway::PAYMENT_METHOD );

		if ( ! is_array( $ordering ) || ! isset( $ordering[ $configId ] ) ) {
			return 0;
		}

		// Exclude Alma frontend gateways (alma_*_gateway except the config gateway).
		$filtered = array_filter(
			$ordering,
			function ( $id ) use ( $configId ) {
				return $id === $configId || ! preg_match( '/^alma_.*_gateway$/', $id );
			},
			ARRAY_FILTER_USE_KEY
		);

		asort( $filtered );
		$keys = array_keys( $filtered );
		$pos  = array_search( $configId, $keys, true );

		return false !== $pos ? (int) $pos + 1 : 0;
	}

	/**
	 * Returns the list of active specific features for this WooCommerce installation.
	 * Currently detects: 'auto_update' if the Alma plugin has automatic updates enabled.
	 *
	 * @return string[]
	 */
	public function getSpecificFeatures(): array {
		$autoUpdatePlugins = get_option( 'auto_update_plugins', array() );
		$pluginBasename    = plugin_basename( Plugin::get_instance()->get_plugin_file() );

		return array_values(
			array_filter(
				array(
					in_array( $pluginBasename, (array) $autoUpdatePlugins, true ) ? 'auto_update' : null,
				)
			)
		);
	}

	/**
	 * Returns true if WordPress multisite is enabled.
	 */
	public function isMultisite(): bool {
		return is_multisite();
	}

	/**
	 * Returns the current WooCommerce version, or an empty string if WooCommerce is not available.
	 */
	public function getCmsVersion(): string {
		if ( ! function_exists( 'WC' ) || ! WC() ) {
			return '';
		}

		return WC()->version ?? '';
	}

	/**
	 * Returns the list of active third-party plugins (excluding the Alma plugin),
	 * each formatted as ['name' => ..., 'version' => ...].
	 *
	 * @return array<int, array{name: string, version: string}>
	 */
	public function getThirdPartiesPlugins(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$allPlugins    = get_plugins();
		$activePlugins = (array) get_option( 'active_plugins', array() );
		$almaBasename  = plugin_basename( Plugin::get_instance()->get_plugin_file() );

		$result = array();
		foreach ( $activePlugins as $basename ) {
			if ( $basename === $almaBasename ) {
				continue;
			}

			if ( isset( $allPlugins[ $basename ] ) ) {
				$result[] = array(
					'name'    => $allPlugins[ $basename ]['Name'],
					'version' => $allPlugins[ $basename ]['Version'],
				);
			}
		}

		return $result;
	}

	/**
	 * Returns the name of the currently active WordPress theme.
	 */
	public function getThemeName(): string {
		return wp_get_theme()->get( 'Name' ) ?: '';
	}

	/**
	 * Returns the version of the currently active WordPress theme.
	 */
	public function getThemeVersion(): string {
		return wp_get_theme()->get( 'Version' ) ?: '';
	}

	/**
	 * Get url to collect CMS data in Alma
	 *
	 * @return string
	 */
	public function getCollectCmsDataUrl(): string {
		return add_query_arg(
			'wc-api',
			CollectCmsDataService::WC_API_ENDPOINT,
			home_url( '/' )
		);
	}
}
