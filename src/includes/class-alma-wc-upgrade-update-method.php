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
class Alma_WC_Upgrade_update_method {

	/**
	* Flag to indicate setting has been loaded from DB.
	*
	* @var bool
	*/
	private $tmp_options = array(
		'woocommerce_alma_settings',
		'alma_warnings_handled'
	);

	/**
	*
	*/
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'new_alma_plugin_activation_hook' ) );

		add_action( 'admin_init',    array( $this, 'alma_wc_admin_init' ) );
		add_action( 'admin_notices', array( $this, 'general_admin_notice' ) );

	}

	/**
	*
	*/
	public function new_alma_plugin_activation_hook() {
		error_log( 'new_alma_plugin_activation_hook()' );
		$this->backup_alma_settings();
		$this->deactivate_old_alma_plugin();
		add_option( ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME, '1' );
	}

	/**
	* Alma admin init action hook callback.
	*
	* @return void
	*/
	public function alma_wc_admin_init() {
		error_log( 'alma_wc_admin_init' );

		//	Pour virer toutes les options Alma.
		//	global $wpdb;
		//	$get_query = $wpdb->query( sprintf( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%s'", 'alma' . '%' ) );
		//	error_log( 'delete from wp_options = ' . $get_query );
		//	exit;

		if ( '1' === get_option( ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME ) ) {
			delete_option( ALMAPAY_PLUGIN_ACTIVATION_OPTION_NAME );
			wp_safe_redirect ( add_query_arg( [ ALMAPAY_PLUGIN_ACTIVATION_FLAG => 1 ], admin_url( '/plugins.php' ) ) );
			exit;
		}

		// Delete old version of Alma plugin.
		if (  isset( $_GET[ ALMAPAY_PLUGIN_ACTIVATION_FLAG ] )  && '1' === $_GET[ ALMAPAY_PLUGIN_ACTIVATION_FLAG ] ) {
			$this->delete_old_alma_plugin();
			$this->import_alma_settings();
		}
	}

	/**
	* Displays a back-office notice.
	*
	* @return void
	*/
	public function general_admin_notice(){
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

		$tmp_option_name = ALMA_PREFIX_FOR_TMP_OPTIONS . $option_name;
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

		$option_value = get_option( ALMA_PREFIX_FOR_TMP_OPTIONS . $option_name );

		delete_option( $option_name );
		update_option( $option_name, $option_value);
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
		if ( is_plugin_active( ALMAPAY_WC_OLD_PLUGIN_FILE) ) {
			error_log( 'ALMAPAY_WC_OLD_PLUGIN_FILE désactivé !' );
			deactivate_plugins( ALMAPAY_WC_OLD_PLUGIN_FILE );
		}
		else {
			error_log( 'ALMAPAY_WC_OLD_PLUGIN_FILE NON désactivé !' );
		}
	}

	/**
	* Delete the old version of Alma plugin.
	*
	* @return void
	*/
	private function delete_old_alma_plugin() {
		delete_plugins( [ ALMAPAY_WC_OLD_PLUGIN_FILE ] );
	}
}
