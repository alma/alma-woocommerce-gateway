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

		if (
			$db_version
			&& version_compare( ALMA_VERSION, $db_version, '!=' )
		) {
			// Old version (< 4.1.0) to 4.2.0 : migration DB + encrypt key.
			$this->update_before_4_1_0( $db_version );

			// Last version = 4.1.0 : encrypt keys.
			$this->update_from_4_0_0( $db_version );
		}

		update_option( 'alma_version', ALMA_VERSION );
	}

	/**
	 * Update < 4.1.0
	 *
	 * @param string $db_version The DB version.
	 *
	 * @return void
	 */
	protected function update_from_4_0_0( $db_version ) {
		// Si la version en BDD est strictement inférieur à la 4.1.0.
		if (
			$db_version
			&& version_compare( $db_version, '4.1.0', '=' )
		) {
			$this->migrate_api_keys();
		}
	}

	/**
	 * Update = 4.1.1
	 *
	 * @param string $db_version The DB version.
	 *
	 * @return void
	 */
	protected function update_before_4_1_0( $db_version ) {
		// Si la version en BDD est strictement inférieur à la 4.1.0.
		if (
			$db_version
			&& version_compare( $db_version, '4.1.0', '<' )
			&& version_compare( ALMA_VERSION, '4.2.0', '=' )
		) {
			$old_settings = get_option( 'woocommerce_alma_settings' );
			update_option( Alma_Settings::OPTIONS_KEY, $old_settings );

			try {
				$this->migrate_api_keys();

				// Manage credentials to match the new settings fields format.
				// Upgrade to 4.
				$gateway = new Alma_Payment_Gateway();

				$gateway->manage_credentials();
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// We don't care if it fails there is nothing to update.
				$this->logger->info( $e->getMessage() );
			}

			update_option( 'alma_version', ALMA_VERSION );
			deactivate_plugins( 'alma-woocommerce-gateway/alma-woocommerce-gateway.php', true );
		}

		update_option( 'alma_version', ALMA_VERSION );
		delete_option( 'woocommerce_alma_settings' );
		delete_option( 'alma_warnings_handled' );
	}

	/**
	 * Encrypt the keys
	 *
	 * @return void
	 */
	protected function migrate_api_keys() {
		$settings    = get_option( Alma_Settings::OPTIONS_KEY );
		$has_changed = false;

		if (
			! empty( $settings['live_api_key'] )
			&& 'sk_live_' === substr( $settings['live_api_key'], 0, 7 )
		) {
			$settings['live_api_key'] = $this->encryptor_helper->encrypt( $settings['live_api_key'] );
			$has_changed              = true;
		}

		if (
			! empty( $settings['test_api_key'] )
			&& 'sk_test_' === substr( $settings['test_api_key'], 0, 7 )
		) {
			$settings['test_api_key'] = $this->encryptor_helper->encrypt( $settings['test_api_key'] );
			$has_changed              = true;
		}

		if ( $has_changed ) {
			update_option( Alma_Settings::OPTIONS_KEY, $settings );
		}
	}
}
