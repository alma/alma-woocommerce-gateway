<?php
/**
 * AlmaBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks
 * @namespace Alma\Woocommerce\Blocks;
 */

namespace Alma\Woocommerce\Blocks;

use Alma\Woocommerce\AlmaSettings;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks
 */
class AlmaWidgetBlock implements IntegrationInterface {

	/**
	 * The settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;

	public function __construct() {
		$this->alma_settings = new AlmaSettings();
	}

	public function get_name() {
		return 'alma-widget-block';
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function initialize() {
		wp_enqueue_style(
			'alma-widget-block-frontend',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@3.x.x/dist/widgets.min.css',
			array(),
			'3.x.x'
		);
		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
	}

	private function register_block_frontend_scripts() {
		$script_path       = '/build/alma-widget-block/alma-widget-block-view.js';
		$script_url        = ALMA_PLUGIN_URL . $script_path;
		$script_asset_path = ALMA_PLUGIN_PATH . '/build/alma-widget-block/alma-widget-block-view.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path // NOSONAR - build PHP script with no class.
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);
		wp_register_script(
			'alma-widget-block-frontend',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@3.x.x/dist/widgets.umd.js',
			array(),
			'3.x.x',
			true
		);
		wp_register_script(
			'alma-widget-block-frontend',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'alma-widget-block-frontend',
			'alma-gateway-for-woocommerce',
			ALMA_PLUGIN_PATH . '/languages'
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 *
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		return ALMA_VERSION;
	}

	private function register_block_editor_scripts() {
		$script_path       = '/build/alma-widget-block/alma-widget-block.js';
		$script_url        = ALMA_PLUGIN_URL . $script_path;
		$script_asset_path = ALMA_PLUGIN_PATH . '/build/alma-widget-block/alma-widget-block.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path // NOSONAR - build PHP script with no class.
			: array(
				'dependencies' => array(),
				'version'      => $this->get_file_version( $script_asset_path ),
			);
		wp_register_script(
			'alma-widget-block-editor',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@3.x.x/dist/widgets.umd.js',
			array(),
			'3.x.x',
			true
		);
		wp_register_script(
			'alma-widget-block-editor',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_set_script_translations(
			'alma-widget-block-frontend',
			'alma-gateway-for-woocommerce',
			ALMA_PLUGIN_PATH . '/languages'
		);
	}

	public function get_script_handles() {
		return array( 'alma-widget-block-frontend' );
	}

	public function get_editor_script_handles() {
		return array( 'alma-widget-block-editor' );
	}

	/**
	 * Send data to the js.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array(
			'merchant_id' => $this->alma_settings->get_active_merchant_id(),
			'environment' => strtoupper( $this->alma_settings->get_environment() ),
		);
	}
}
