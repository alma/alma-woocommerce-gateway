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
use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\GatewayHelperBuilder;
use Alma\Woocommerce\Helpers\CartHelper;
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
	/**
	 * @var CartHelper
	 */
	private $cart_helper;
	/**
	 * The gateway helper.
	 * @var GatewayHelper
	 */
	private $gateway_helper;

	public function __construct() {
		$this->alma_settings    = new AlmaSettings();
		$cart_helper_builder    = new CartHelperBuilder();
		$this->cart_helper      = $cart_helper_builder->get_instance();
		$gateway_helper_builder = new GatewayHelperBuilder();
		$this->gateway_helper   = $gateway_helper_builder->get_instance();
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
			ALMA_PLUGIN_URL . '/build/alma-widget-block/alma-widget-block-view.css',
			array(),
			$this->get_file_version( ALMA_PLUGIN_URL . '/build/alma-widget-block/alma-widget-block-view.css' )
		);
		wp_enqueue_style(
			'alma-widget-block-frontend-cdn',
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.min.css',
			array(),
			'4.x.x'
		);

		$this->register_block_frontend_scripts();
		$this->register_block_editor_scripts();
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
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
			array(),
			'4.x.x',
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
			'https://cdn.jsdelivr.net/npm/@alma/widgets@4.x.x/dist/widgets.umd.js',
			array(),
			'4.x.x',
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
			'merchant_id'      => $this->alma_settings->get_active_merchant_id(),
			'environment'      => strtoupper( $this->alma_settings->get_environment() ),
			'plans'            => $this->filter_plans_definitions( $this->alma_settings->get_enabled_plans_definitions() ),
			'amount'           => $this->cart_helper->get_total_in_cents(),
			'locale'           => substr( get_locale(), 0, 2 ),
			'can_be_displayed' => $this->can_be_displayed(),
		);
	}

	/**
	 * Filter & format enabled plans to match data-settings.enabledPlans allowed value.
	 *
	 * @param array $plans_settings Plans definitions to filter & format.
	 *
	 * @return array
	 */
	protected function filter_plans_definitions( $plans_settings ) {
		return array_values( // Remove plan_keys from enabled plans definitions.
			array_filter(
				$plans_settings,
				function ( $plan_definition ) {
					if ( ! isset( $plan_definition['installments_count'] ) ) { // Widget does not work fine without installments_count.
						return false;
					}

					return true;
				}
			)
		);
	}

	private function can_be_displayed() {
		return $this->alma_settings->has_keys()
			   && $this->alma_settings->is_enabled()
			   && 'yes' === $this->alma_settings->display_cart_eligibility
			&& ! $this->gateway_helper->cart_contains_excluded_category();
	}
}
