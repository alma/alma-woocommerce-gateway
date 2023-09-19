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
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Exceptions\Alma_Version_Deprecated;
use Alma\Woocommerce\Gateways\Standard\Alma_Payment_Gateway_Standard;

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

	/**
	 * Update plugin.
	 *
	 * @throws Alma_Version_Deprecated The exception.
	 * @return bool Is the migration ok.
	 */
	public function update() {
		$db_version = alma_get_option( 'alma_version' );

		if ( ! $db_version ) {
			$db_version = get_option( 'alma_version' );
		}

		$flag_migration = alma_get_option( 'alma_migration_ongoing' );

		if ( $flag_migration ) {
			// ongoing or failed migration, don't do anything !
			return false;
		}

		if ( version_compare( ALMA_VERSION, $db_version, '=' ) ) {
			return true;
		}

		alma_add_option( 'alma_migration_ongoing', ALMA_VERSION );
		alma_update_option( 'alma_previous_version', $db_version );

		$this->manage_versions( $db_version );

		alma_update_option( 'alma_version', ALMA_VERSION );
		alma_delete_option( 'alma_migration_ongoing' );

		return true;
	}

	/**
	 * Manage the migrations.
	 *
	 * @param string $db_version The DB version.
	 *
	 * @throws Alma_Version_Deprecated The exception.
	 *
	 * @return void
	 */
	public function manage_versions( $db_version ) {
		if (
			$db_version
			&& version_compare(
				ALMA_VERSION,
				$db_version,
				'>'
			)
		) {
			$this->manage_version_before_3( $db_version );
			$this->migrate_keys();

			alma_delete_option( 'woocommerce_alma_settings' );
			alma_delete_option( 'alma_warnings_handled' );
		}
	}

	/**
	 * Migrate the keys in db.
	 *
	 * @return void
	 */
	protected function migrate_keys() {
		try {
			$get_credentials = false;
			$has_changed     = false;

			$old_settings = get_option( 'woocommerce_alma_settings' );

			if ( $old_settings ) {
				alma_update_option( Alma_Settings::OPTIONS_KEY, $old_settings );
				$get_credentials = true;
			}

			$settings = alma_get_option( Alma_Settings::OPTIONS_KEY );

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
				alma_update_option( Alma_Settings::OPTIONS_KEY, $settings );
			}

			// Test if we need to call the api in order to manage the in-page.
			if ( version_compare( ALMA_VERSION, 5, '>=' ) ) {
				$get_credentials = true;
			}

			if ( $get_credentials ) {
				// Manage credentials to match the new settings fields format.
				$gateway = new Alma_Payment_Gateway_Standard( false );

				$gateway->manage_credentials();
			}
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// We don't care if it fails there is nothing to update.
			$this->logger->info( $e->getMessage() );
		}
	}

	/**
	 * Manage version before 3.* .
	 *
	 * @param string $db_version The DB version.
	 *
	 * @return void
	 * @throws Alma_Version_Deprecated The exception.
	 */
	protected function manage_version_before_3( $db_version ) {
		if ( version_compare( $db_version, 3, '<' ) ) {
			throw new Alma_Version_Deprecated( $db_version );
		}
	}

}
