<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\Gateway\Infrastructure\Config\AssetsConfig;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;

class AssetsService {

	private array $registered_assets = [];

	public function __construct() {
		// @todo check with Martin if we want to load all assets config here
		$this->registered_assets = AssetsConfig::get_all();
	}

	// @todo Do we need this function ?
	public function register_group( $group_name, $assets ) {
		$this->registered_assets[ $group_name ] = $assets;
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
	public function loadCheckoutBlockAssets( array $scriptParams = [] ): void {
		$this->enqueueGroup( AssetsConfig::ASSETS_CONFIG_CHECKOUT_BLOCK, $scriptParams );
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

				// Handle localization
				if ( isset( $config['params'] ) ) {

					$expectedKeys = array_keys( $config['params']['keys'] );
					$scriptParams = array_intersect_key( $scriptParams, array_flip( $expectedKeys ) );

					wp_localize_script(
						$handle,
						$config['params']['object_name'],
						$scriptParams
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