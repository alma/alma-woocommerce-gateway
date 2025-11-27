<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\Gateway\Infrastructure\Config\AssetsConfig;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;

class AssetsService {

	private array $registered_assets = [];

	/**
	 * CDN Assets are loaded by default.
	 * @throws AssetsServiceException
	 * @todo CDN should be only register and not be enqueued by default
	 */
	public function __construct() {
		$this->registered_assets = AssetsConfig::getAll();
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_CDN );
	}

	/**
	 * @throws AssetsServiceException
	 */
	public function enqueueGroup( string $group_name, array $scriptParams = [] ): void {
		if ( ! isset( $this->registered_assets[ $group_name ] ) ) {
			throw new AssetsServiceException( 'Assets are not defined in config.' );
		}

		$assets = $this->registered_assets[ $group_name ];

		$this->enqueuePhp( $assets );
		$this->enqueueStyles( $assets );
		$this->enqueueScripts( $assets, $scriptParams );
	}

	/**
	 * Load Widget assets.
	 * @throws AssetsServiceException
	 */
	public function loadWidgetAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_WIDGET, $scriptParams );
	}

	/**
	 * Load In-Page assets.
	 * @throws AssetsServiceException
	 */
	public function loadInPageAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_IN_PAGE, $scriptParams );
	}

	/**
	 * Load Widget Block assets.
	 * @throws AssetsServiceException
	 */
	public function loadWidgetBlockAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_WIDGET_BLOCK, $scriptParams );
	}

	/**
	 * Load Widget Block assets.
	 * @throws AssetsServiceException
	 */
	public function loadWidgetBlockEditorAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_WIDGET_BLOCK_EDITOR, $scriptParams );
	}

	/**
	 * Load Checkout Block assets.
	 * @throws AssetsServiceException
	 */
	public function loadGatewayBlockAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_GATEWAY_BLOCK, $scriptParams );
	}

	/**
	 * Load Admin assets.
	 * @throws AssetsServiceException
	 */
	public function loadAdminAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_ADMIN, $scriptParams );
	}

	/**
	 * Load Block assets.
	 */
	private function enqueuePhp( array $assets ): void {
		foreach ( $assets as $asset ) {
			if ( isset( $asset['php']['src'] ) && file_exists( $asset['php']['src'] ) ) {
				require_once $asset['php']['src'];
			}
		}
	}

	/**
	 * Enqueue styles.
	 *
	 * @param array $assets
	 *
	 * @return void
	 */
	private function enqueueStyles( array $assets ) {
		// Enqueue styles first
		if ( isset( $assets['styles'] ) ) {
			foreach ( $assets['styles'] as $handle => $config ) {
				wp_enqueue_style(
					$handle,
					$config['src'],
					$config['deps'] ?? [],
					$config['version'] ?? AssetsHelper::getFileVersion( $config['src'] ),
					$config['media'] ?? 'all'
				);
			}
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param array $assets
	 * @param array $scriptParams
	 *
	 * @return void
	 */
	private function enqueueScripts( array $assets, array $scriptParams ) {
		// Then scripts
		if ( isset( $assets['scripts'] ) ) {
			foreach ( $assets['scripts'] as $handle => $config ) {
				wp_enqueue_script(
					$handle,
					$config['src'],
					$config['deps'] ?? [],
					$config['version'] ?? AssetsHelper::getFileVersion( $config['src'] ),
					$config['in_footer'] ?? true
				);

				// Handle localization @todo use window.wc.wcSettings.getSetting instead?
				if ( isset( $config['params'] ) ) {

					$expectedKeys         = array_flip( $config['params']['keys'] );
					$filteredScriptParams = array_intersect_key( $scriptParams, $expectedKeys );

					wp_localize_script(
						$handle,
						$config['params']['object_name'],
						$filteredScriptParams
					);
				}

				// Handle translations
				if ( isset( $config['translations'] ) ) {
					wp_set_script_translations(
						$handle,
						$config['translations']['domain'],
						$config['translations']['path']
					);
				}
			}
		}
	}
}
