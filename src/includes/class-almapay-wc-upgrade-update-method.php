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

	// @todo en vrai il faudra virer "-2.6.1"
	const ALMAPAY_WC_OLD_PLUGIN_FILE = 'alma-woocommerce-gateway-2.6.1/alma-woocommerce-gateway.php';

	// @todo en vrai ça sera "alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php"
//	const ALMAPAY_WC_NEW_PLUGIN_FILE = 'alma-woocommerce-gateway/alma-gateway-for-woocommerce.php';

	const ALMAPAY_PREFIX_FOR_TMP_OPTIONS        = 'almapay_tmp_';
	const ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME = 'almapay_new_plugin_activation_running';
	const ALMAPAY_PLUGIN_ACTIVATION_FLAG        = 'almapay_new_installed';

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
		error_log( 'new_alma_plugin_activation_hook()' );
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
		error_log( 'Almapay_WC_admin_init' );

		// Pour virer toutes les options Alma.
		// global $wpdb;
		// $get_query = $wpdb->query( sprintf( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%s'", 'alma' . '%' ) );
		// error_log( 'delete from wp_options = ' . $get_query );
		// exit;

		if ( '1' === get_option( self::ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME ) ) {
			delete_option( self::ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME );
			wp_safe_redirect( add_query_arg( array( self::ALMAPAY_PLUGIN_ACTIVATION_FLAG => 1 ), admin_url( '/plugins.php' ) ) );
			exit;
		}

		// Delete old version of Alma plugin.
		if ( isset( $_GET[ self::ALMAPAY_PLUGIN_ACTIVATION_FLAG ] ) && '1' === $_GET[ self::ALMAPAY_PLUGIN_ACTIVATION_FLAG ] ) {
			$this->delete_old_alma_plugin();
			$this->import_alma_settings();
		}
	}

	/**
	 * Displays a back-office notice.
	 *
	 * @return void
	 */
	public function admin_notices() {
		global $pagenow;
		if ( $pagenow === 'plugins.php' ) {
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

		error_log( '-----------------------------' );
		error_log( 'backup_alma_settings()' );
		error_log( '-----------------------------' );

		foreach ( $this->tmp_options  as $option_name ) {

			$tmp_option_name = self::ALMAPAY_PREFIX_FOR_TMP_OPTIONS . $option_name;
			$option_value    = get_option( $option_name );

			delete_option( $tmp_option_name );
			update_option( $tmp_option_name, $option_value );
			error_log( 'update_option : $tmp_option_name = ' . $tmp_option_name . ' et $option_value = ' . serialize( $option_value ) );
		}
	}

	/**
	 * Backups the plugin options.
	 *
	 * @return void
	 */
	private function import_alma_settings() {

		error_log( '-----------------------------' );
		error_log( 'import_alma_settings()' );
		error_log( '-----------------------------' );

		foreach ( $this->tmp_options  as $option_name ) {

			$option_value = get_option( self::ALMAPAY_PREFIX_FOR_TMP_OPTIONS . $option_name );

			delete_option( $option_name );
			update_option( $option_name, $option_value );
			error_log( 'update_option : $option_name = ' . $option_name . ' et $option_value = ' . serialize( $option_value ) );
		}
		error_log( '---------------------------------------------------' );
	}

	/**
	 * Deactivate the old version of Alma plugin.
	 *
	 * @return void
	 */
	private function deactivate_old_alma_plugin() {
		if ( is_plugin_active( self::ALMAPAY_WC_OLD_PLUGIN_FILE ) ) {
			error_log( 'self::ALMAPAY_WC_OLD_PLUGIN_FILE désactivé !' );
			deactivate_plugins( self::ALMAPAY_WC_OLD_PLUGIN_FILE );
		} else {
			error_log( 'self::ALMAPAY_WC_OLD_PLUGIN_FILE NON désactivé !' );
		}
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
