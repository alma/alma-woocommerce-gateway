<?php

namespace Alma\Gateway\Infrastructure\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Config\AssetsConfig;
use Alma\Gateway\Infrastructure\Exception\Service\AssetsServiceException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;

class AssetsService {

	private array $registered_assets = [];

	/**
	 * CDN Assets are loaded by default.
	 * @throws AssetsServiceException
	 */
	public function __construct() {
		$this->registered_assets = AssetsConfig::getAll();
		$this->registerCdn( AssetsConfig::ASSETS_CONFIG_CDN );
	}

	/**
	 * @throws AssetsServiceException
	 */
	public function registerLocal( string $group_name, array $scriptParams = [] ): void {
		if ( ! isset( $this->registered_assets[ $group_name ] ) ) {
			throw new AssetsServiceException( 'Assets are not defined in config.' );
		}

		$assets = $this->registered_assets[ $group_name ];

		$this->registerPhp( $assets );
		$this->enqueueStyles( $assets );
		$this->registerScripts( $assets, $scriptParams );
	}

	/**
	 * @throws AssetsServiceException
	 */
	public function registerCdn( string $group_name ): void {
		if ( ! isset( $this->registered_assets[ $group_name ] ) ) {
			throw new AssetsServiceException( 'Assets are not defined in config.' );
		}

		$assets = $this->registered_assets[ $group_name ];

		$this->registerCdnStyles( $assets );
		$this->registerCdnScripts( $assets );
	}

	/**
	 * Prepare In-Page assets.
	 * @throws AssetsServiceException
	 */
	public function registerInPageAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_IN_PAGE, $scriptParams );
	}

	/**
	 * Display In-Page assets.
	 */
	public function displayInPageAssets(): void {
		wp_enqueue_script( 'alma-' . AssetsConfig::ASSETS_CONFIG_IN_PAGE );
	}

	/**
	 * Prepare Widget assets.
	 * @throws AssetsServiceException
	 */
	public function registerWidgetAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_WIDGET, $scriptParams );
	}

	/**
	 * Display Widget assets.
	 */
	public function displayWidgetAssets(): void {
		wp_enqueue_script( 'alma-' . AssetsConfig::ASSETS_CONFIG_WIDGET );
	}

	/**
	 * Prepare Widget Block assets.
	 * @throws AssetsServiceException
	 */
	public function registerWidgetBlockAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_WIDGET_BLOCK, $scriptParams );
	}

	/**
	 * Prepare Widget Block Editor assets.
	 * @throws AssetsServiceException
	 */
	public function registerWidgetBlockEditorAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_WIDGET_BLOCK_EDITOR, $scriptParams );
	}

	/**
	 * Prepare Checkout Block assets.
	 * @throws AssetsServiceException
	 */
	public function registerGatewayBlockAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_GATEWAY_BLOCK, $scriptParams );
	}

	/**
	 * Load Checkout assets.
	 * @throws AssetsServiceException
	 */
	public function registerClassicCheckoutAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_CLASSIC_CHECKOUT, $scriptParams );
	}

	/**
	 * Display Checkout assets.
	 */
	public function displayClassicCheckoutAssets(): void {
		wp_enqueue_script( 'alma-' . AssetsConfig::ASSETS_CONFIG_CLASSIC_CHECKOUT );
	}

	/**
	 * Load Admin assets.
	 * Prepare Admin assets.
	 * @throws AssetsServiceException
	 */
	public function registerAdminAssets( array $scriptParams = [] ): void {
		$this->registerLocal( AssetsConfig::ASSETS_CONFIG_ADMIN, $scriptParams );
	}

	/**
	 * Display Admin assets.
	 */
	public function displayAdminAssets(): void {
		wp_enqueue_script( 'alma-' . AssetsConfig::ASSETS_CONFIG_ADMIN );
	}

	/**
	 * Load Block assets.
	 */
	private function registerPhp( array $assets ): void {
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
	private function registerScripts( array $assets, array $scriptParams ) {
		// Then scripts
		if ( isset( $assets['scripts'] ) ) {
			foreach ( $assets['scripts'] as $handle => $config ) {
				wp_register_script(
					$handle,
					$config['src'],
					$config['deps'] ?? [],
					$config['version'] ?? AssetsHelper::getFileVersion( $config['src'] ),
					$config['in_footer'] ?? true
				);

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

	/**
	 * Register styles.
	 *
	 * @param array $assets
	 *
	 * @return void
	 */
	private function registerCdnStyles( array $assets ) {
		// Enqueue styles first
		if ( isset( $assets['styles'] ) ) {
			foreach ( $assets['styles'] as $handle => $config ) {
				wp_register_style(
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
	 * Register scripts.
	 *
	 * @param array $assets
	 *
	 * @return void
	 */
	private function registerCdnScripts( array $assets ) {
		// Then scripts
		if ( isset( $assets['scripts'] ) ) {
			foreach ( $assets['scripts'] as $handle => $config ) {
				wp_register_script(
					$handle,
					$config['src'],
					$config['deps'] ?? [],
					$config['version'] ?? AssetsHelper::getFileVersion( $config['src'] ),
					$config['in_footer'] ?? true
				);
			}
		}
	}
}
