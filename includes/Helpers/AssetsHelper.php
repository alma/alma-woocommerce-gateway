<?php
/**
 * AssetsHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AssetsHelper
 */
class AssetsHelper {


	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_url( $path ) {
		return ALMA_PLUGIN_URL . 'assets/' . $path;
	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_build_url( $path ) {
		return ALMA_PLUGIN_URL . 'build/' . $path;
	}

	/**
	 * Get admin logs url.
	 *
	 * @return string
	 */
	public static function get_admin_logs_url() {
		return admin_url( 'admin.php?page=wc-status&tab=logs' );
	}

	/**
	 * Get the admin asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public static function get_asset_admin_url( $path ) {
		return ALMA_PLUGIN_URL . 'assets/admin/' . $path;
	}

	/**
	 * Link to settings screen.
	 *
	 * @param bool $alma_section Go to alma section.
	 *
	 * @return string
	 */
	public function get_admin_setting_url( $alma_section = true ) {
		if ( $alma_section ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma' );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout' );
	}

	/**
	 * Get Alma full URL depends on test or live mode (sandbox or not)
	 *
	 * @param string $env The environment.
	 * @param string $path as path to add after default scheme://host/ infos.
	 *
	 * @return string as full URL
	 */
	public static function get_alma_dashboard_url( $env = 'test', $path = '' ) {
		if ( 'live' === $env ) {
			/* translators: %s -> path to add after dashboard url */
			return esc_url( sprintf( __( 'https://dashboard.getalma.eu/%s', 'alma-gateway-for-woocommerce' ), $path ) );
		}

		/* translators: %s -> path to add after sandbox dashboard url */

		return esc_url( sprintf( __( 'https://dashboard.sandbox.getalma.eu/%s', 'alma-gateway-for-woocommerce' ), $path ) );
	}

	/**
	 * Get Alma doc for in page.
	 *
	 * @return string
	 */
	public static function get_in_page_doc_link() {
		return esc_url( 'https://docs.almapay.com/docs/in-page-woocommerce' );
	}


	/**
	 * Get Blocks doc.
	 *
	 * @return string
	 */
	public static function get_blocks_doc_link() {
		return esc_url( 'https://woocommerce.com/document/woocommerce-blocks/#template-blocks' );
	}

	/**
	 *  Enqueue scripts needed into admin form.
	 *
	 * @return void
	 */
	public function alma_admin_enqueue_scripts() {
		wp_enqueue_style( 'alma-admin-styles', ALMA_PLUGIN_URL . 'assets/admin/css/alma.css', array(), ALMA_VERSION );

		wp_enqueue_script(
			'alma-admin-scripts',
			ALMA_PLUGIN_URL . 'assets/admin/js/alma.js',
			array(
				'jquery',
				'jquery-effects-highlight',
				'jquery-ui-selectmenu',
			),
			ALMA_VERSION,
			true
		);

		wp_localize_script(
			'alma-admin-scripts',
			'alma_admin_params',
			array(
				'block_confirmation' => __( 'Are you sure you want to enable compatibility with the Order Validation Block? Please note that this WooCommerce Block may not be fully compatible with all themes, potentially resulting in bugs. If you encounter any issues with the Alma payment functionality, we recommend deactivating this setting.', 'alma-gateway-for-woocommerce' ),
			)
		);

	}

	/**
	 * Get the html for the image to display
	 *
	 * @param string $title the alt attribute.
	 * @param string $id Used for the filter.
	 * @param string $path By default, the alma logo.
	 *
	 * @return string
	 */
	public static function get_icon( $title, $id, $path = ConstantsHelper::ALMA_LOGO_PATH ) {
		$icon_url = static::get_asset_url( $path );
		$icon     = '<img src="' . \WC_HTTPS::force_https_url( $icon_url ) . '" alt="' . esc_attr( $title ) . '" style="width: auto !important; height: 25px !important; border: none !important;">';

		return apply_filters( 'alma_wc_gateway_icon', $icon, $id );
	}

	/**
	 * Allow Alma domains for redirect.
	 *
	 * @param string[] $domains Whitelisted domains for `wp_safe_redirect`.
	 *
	 * @return string[]
	 */
	public function alma_domains_whitelist( $domains ) {
		$domains[] = 'pay.getalma.eu';
		$domains[] = 'pay.sandbox.getalma.eu';

		return $domains;
	}
}
