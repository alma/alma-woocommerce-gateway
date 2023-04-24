<?php
/**
 * Alma_Migration_Helper.
 *
 * @since 4.1.1
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Alma_Payment_Gateway;
use Alma\Woocommerce\Alma_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alma_Migration_Helper
 */
class Alma_Migration_Helper {

	/**
	 * The encryptor Helper.
	 *
	 * @var Alma_Encryptor_Helper
	 */
	protected $encryptor_helper;


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->encryptor_helper = new Alma_Encryptor_Helper();
		$this->logger           = new Alma_Logger();
	}

	/** Update the plugin
	 *
	 * @return void
	 */
	public function update() {
		$db_version = get_option( 'alma_version' );

		if ( version_compare( ALMA_VERSION, $db_version, '=' ) ) {
			return;
		}

		$this->manage_versions( $db_version );

		update_option( 'alma_version', ALMA_VERSION );
	}

	/**
	 * Manage the migrations.
	 *
	 * @param string $db_version    The db version.
	 * @return void
	 */
	public function manage_versions( $db_version ) {
		if (
			$db_version
			&& version_compare( ALMA_VERSION, $db_version, '>' )
			&& version_compare( $db_version, '4.1.2', '<=' )
		) {
			// Si la version en BDD est strictement inférieur à la 4.1.2
			$this->migrate_keys();
			$this->manage_version_before_3( $db_version );

			delete_option( 'woocommerce_alma_settings' );
			delete_option( 'alma_warnings_handled' );
		}
	}

	/**
	 * Migrate the keys in db.
	 *
	 * @return void
	 */
	protected function migrate_keys() {
		$old_settings = get_option( 'woocommerce_alma_settings' );

		if ( $old_settings ) {
			update_option( Alma_Settings::OPTIONS_KEY, $old_settings );
		}

		$settings    = get_option( Alma_Settings::OPTIONS_KEY );
		$has_changed = false;

		try {
			if (
				! empty( $settings['live_api_key'] )
				&& 'sk_live_' === substr( $settings['live_api_key'], 0, 8 )
			) {
				$settings['live_api_key'] = $this->encryptor_helper->encrypt( $settings['live_api_key'] );
				$has_changed              = true;
			}

			if (
				! empty( $settings['test_api_key'] )
				&& 'sk_test_' === substr( $settings['test_api_key'], 0, 8 )
			) {
				$settings['test_api_key'] = $this->encryptor_helper->encrypt( $settings['test_api_key'] );
				$has_changed              = true;
			}

			if ( $has_changed ) {
				update_option( Alma_Settings::OPTIONS_KEY, $settings );
			}

			// Manage credentials to match the new settings fields format.

			// Upgrade to 4.
			$gateway = new Alma_Payment_Gateway();

			$gateway->manage_credentials( true );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// We don't care if it fails there is nothing to update.
			$this->logger->info( $e->getMessage() );
		}
	}

	/**
	 * Manage version before 3.* .
	 *
	 * @param string $db_version The DB version.
	 * @return void
	 */
	protected function manage_version_before_3( $db_version ) {
		if ( version_compare( $db_version, 3, '<' ) ) {
			update_option( 'alma_version', ALMA_VERSION );
			deactivate_plugins( 'alma-woocommerce-gateway/alma-woocommerce-gateway.php', true );
		}
	}

}
