<?php
/**
 * Alma settings
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles migration of the plugin from old style to new style (on wordpress.org).
 *
 * @property array tmp_options Array of Alma's options to back up and import.
 */
class Almapay_WC_Upgrade_update_method {

	// @todo in real life, remove "-2.6.1".
	const ALMAPAY_WC_OLD_PLUGIN_FILE            = 'alma-woocommerce-gateway-2.6.1/alma-woocommerce-gateway.php';
	const ALMAPAY_PREFIX_FOR_TMP_OPTIONS        = 'almapay_tmp_';
	const ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME = 'almapay_new_plugin_activation_running';
	const ALMAPAY_PLUGIN_ACTIVATION_FLAG        = 'almapay_new_installed';
	const ALMAPAY_PLUGIN_REMOVE_FLAG            = 'almapay_old_removed';

	/**
	 * Flag to indicate setting has been loaded from DB.
	 *
	 * @var bool
	 */
	private $tmp_options = array(
		'woocommerce_alma_settings',
		'alma_warnings_handled',
	);

	/**
	 *
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'Almapay_WC_admin_init' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 *
	 */
	public function new_alma_plugin_activation_hook() {
		$this->backup_alma_settings();
		$this->deactivate_old_alma_plugin();
		add_option( self::ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME, '1' );
	}

	/**
	 * Alma admin init action hook callback.
	 *
	 * @return void
	 */
	public function Almapay_WC_admin_init() {

		// Redirection after deactivating old plugin to refresh plugins page with new plugins status.
		if ( '1' === get_option( self::ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME ) ) {
			delete_option( self::ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME );
			wp_safe_redirect( add_query_arg( array( self::ALMAPAY_PLUGIN_ACTIVATION_FLAG => 1 ), admin_url( '/plugins.php' ) ) );
			exit;
		}

		// Delete old version of Alma plugin.
		if ( isset( $_GET[ self::ALMAPAY_PLUGIN_ACTIVATION_FLAG ] ) && '1' === $_GET[ self::ALMAPAY_PLUGIN_ACTIVATION_FLAG ] ) {
			$this->delete_old_alma_plugin();
			$this->import_alma_settings();
			wp_safe_redirect( add_query_arg( array( self::ALMAPAY_PLUGIN_REMOVE_FLAG => 1 ), admin_url( '/plugins.php' ) ) );
		}
	}

	/**
	 * Displays a back-office notice after the new plugin has been installed and the old one has been removed.
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( isset( $_GET[ self::ALMAPAY_PLUGIN_REMOVE_FLAG ] ) && '1' === $_GET[ self::ALMAPAY_PLUGIN_REMOVE_FLAG ] ) {
			echo '<div class="notice updated is-dismissible"><p>' .
				__( 'The new version of Alma plugin has successfully been installed and the old version has been removed. Thank you for this update!', 'alma-gateway-for-woocommerce' ) .
				'</p></div>';
		}
	}

	/**
	 * Backups the plugin settings, with different option names.
	 *
	 * @return void
	 */
	private function backup_alma_settings() {
		foreach ( $this->tmp_options  as $option_name ) {
			$tmp_option_name = self::ALMAPAY_PREFIX_FOR_TMP_OPTIONS . $option_name;
			$option_value    = get_option( $option_name );
			delete_option( $tmp_option_name );
			update_option( $tmp_option_name, $option_value );
		}
	}

	/**
	 * Backups the plugin options.
	 *
	 * @return void
	 */
	private function import_alma_settings() {
		foreach ( $this->tmp_options  as $option_name ) {
			$option_value = get_option( self::ALMAPAY_PREFIX_FOR_TMP_OPTIONS . $option_name );
			delete_option( $option_name );
			update_option( $option_name, $option_value );
		}
	}

	/**
	 * Deactivate the old version of Alma plugin.
	 *
	 * @return void
	 */
	private function deactivate_old_alma_plugin() {
		deactivate_plugins( self::ALMAPAY_WC_OLD_PLUGIN_FILE );
	}

	/**
	 * Delete the old version of Alma plugin.
	 *
	 * @return void
	 */
	private function delete_old_alma_plugin() {
		delete_plugins( array( self::ALMAPAY_WC_OLD_PLUGIN_FILE ) );
	}
}
