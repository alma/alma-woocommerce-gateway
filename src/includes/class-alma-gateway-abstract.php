<?php
/**
 * Alma_Gateway_Abstract.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 *
 * @since 4.3.0
 */

namespace Alma\Woocommerce;

use Alma\Woocommerce\Admin\Helpers\Alma_Check_Legal_Helper;
use Alma\Woocommerce\Admin\Helpers\Alma_Form_Helper;
use Alma\Woocommerce\Exceptions\Alma_Api_Client_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Merchants_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Plans_Exception;
use Alma\Woocommerce\Exceptions\Alma_No_Credentials_Exception;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Checkout_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Helpers\Alma_Encryptor_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Alma\Woocommerce\Helpers\Alma_General_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;

}

/**
 * Alma_Gateway_Abstract
 */
class Alma_Gateway_Abstract extends \WC_Payment_Gateway {

	public function __construct() {
		 $this->logger            = new Alma_Logger();
		$this->alma_settings      = new Alma_Settings();
		$this->check_legal_helper = new Alma_Check_Legal_Helper();
		$this->checkout_helper    = new Alma_Checkout_Helper();
		$this->gateway_helper     = new Alma_Gateway_Helper();
		$this->general_helper     = new Alma_General_Helper();
		$this->scripts_helper     = new Alma_Assets_Helper();
		$this->plan_builder       = new Alma_Plan_Builder();
		$this->encryption_helper  = new Alma_Encryptor_Helper();
		$this->tool_helper        = new Alma_Tools_Helper();

		$this->check_activation();

		$this->check_alma_keys( false );
		$this->add_filters();
		$this->add_actions();
		$this->init_admin_form();
		$this->check_legal_helper->check_share_checkout();
	}

	/**
	 * Check plugin activation.
	 *
	 * @return void
	 */
	public function check_activation() {
		$enabled = $this->get_option( 'enabled', 'no' );

		if ( ! Alma_Tools_Helper::alma_string_to_bool( $enabled ) ) {
			$message = sprintf(
			// translators: %s: Admin settings url.
				__( "Thanks for installing Alma! Start by <a href='%s'>activating Alma's payment method</a>, then set it up to get started.", 'alma-gateway-for-woocommerce' ),
				esc_url( Alma_Assets_Helper::get_admin_setting_url( false ) )
			);
			alma_plugin()->admin_notices->add_admin_notice( 'no_alma_enabled', 'notice notice-warning', $message );
		}
	}

	/**
	 * Checks the api keys
	 *
	 * @param bool $throw_exception Do we want to throw the exception.
	 * @return void
	 * @throws Alma_No_Credentials_Exception No credentials.
	 */
	public function check_alma_keys( $throw_exception = true ) {
		// Do we have keys for the environment?
		if ( ! $this->alma_settings->has_keys() ) { // nope.
			$message = sprintf(
			// translators: %s: Admin settings url.
				__( 'Alma is almost ready. To get started, <a href="%s">fill in your API keys</a>.', 'alma-gateway-for-woocommerce' ),
				esc_url( Alma_Assets_Helper::get_admin_setting_url() )
			);

			alma_plugin()->admin_notices->add_admin_notice( 'no_alma_keys', 'notice notice-warning', $message );

			if ( $throw_exception ) {
				throw new Alma_No_Credentials_Exception( $message );
			}
		}
	}

	/**
	 * Add filters.
	 *
	 * @return void
	 */
	public function add_filters() {
		add_filter(
			'woocommerce_available_payment_gateways',
			array(
				$this->gateway_helper,
				'woocommerce_available_payment_gateways',
			)
		);

		add_filter( 'woocommerce_gateway_title', array( $this->gateway_helper, 'woocommerce_gateway_title' ), 10, 2 );
		add_filter( 'allowed_redirect_hosts', array( $this->general_helper, 'alma_domains_whitelist' ) );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	/**
	 *  Add the actions.
	 *
	 * @return void
	 */
	public function add_actions() {
		add_action( 'woocommerce_before_checkout_process', array( $this->checkout_helper, 'woocommerce_checkout_process' ), 1 );

		$payment_upon_trigger_helper = new Alma_Payment_Upon_Trigger();
		add_action(
			'woocommerce_order_status_changed',
			array(
				$payment_upon_trigger_helper,
				'woocommerce_order_status_changed',
			),
			10,
			3
		);

	}

	/**
	 *  Initialize the admin form.
	 *
	 * @return void
	 */
	public function init_admin_form() {

		list($tab, $section) = $this->get_tab_and_section();

		if (
			is_admin()
			&& 'checkout' == $tab
			&&
			(
				Alma_Constants_Helper::GATEWAY_ID == $section
				|| Alma_Constants_Helper::GATEWAY_PN_ID == $section
			)
		) {
			$this->show_alma_fee_plans = false;

			try {
				$this->manage_credentials();
				$this->show_alma_fee_plans = true;
			} catch ( \Exception $e ) {
				$this->logger->error( $e->getMessage() );
			}

			add_action( 'admin_enqueue_scripts', array( $this->scripts_helper, 'alma_admin_enqueue_scripts' ) );

			$this->alma_settings->load_settings();
			$this->init_form_fields();
		}
	}

	/**
	 *  Get the current tab and section.
	 *
	 * @return array
	 */
	protected function get_tab_and_section() {
		global $current_tab, $current_section;
		$tab     = $current_tab;
		$section = $current_section;

		if (
			(
				empty( $tab )
				|| empty( $section )
			)
			&& ! empty( $_SERVER['QUERY_STRING'] )
		) {
			$query_parts = explode( '&', $_SERVER['QUERY_STRING'] );

			foreach ( $query_parts as $args ) {
				$query_args = explode( '=', $args );

				if ( count( $query_args ) == 2 ) {
					switch ( $query_args['0'] ) {
						case 'tab':
							$tab = $query_args['1'];
							break;
						case 'section':
							$section = $query_args['1'];
							break;
						default:
							break;
					}
				}
			}
		}

		return array( $tab, $section );
	}
	/**
	 *  Manage the credentials, check the clients and manage plans.
	 *
	 *  @param bool $check_merchant Call the merchant endpoint.
	 *
	 * @return void
	 * @throws Alma_Api_Client_Exception Client Api exception.
	 * @throws Alma_Api_Merchants_Exception Merchant Api exception.
	 * @throws Alma_Api_Plans_Exception  Plans Api exception.
	 * @throws Alma_No_Credentials_Exception No credentials Api excetption.
	 */
	public function manage_credentials( $check_merchant = false ) {
		$this->check_alma_keys();
		$this->init_alma_client();

		if ( $check_merchant ) {
			// We store the old merchant id.
			$old_merchant_id = $this->alma_settings->get_active_merchant_id();

			$this->init_alma_merchant();

			// If the merchant_id has changed we need to update it in the database.
			$merchant_id = $this->alma_settings->get_active_merchant_id();

			if ( $merchant_id !== $old_merchant_id ) {
				$this->alma_settings->settings[ $this->alma_settings->environment . '_merchant_id' ] = $merchant_id;
			}
		}

		$this->alma_settings->init_allowed_fee_plans();

		// Save of the fee plans.
		update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->alma_settings->settings ), 'yes' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Get the alma client.
	 *
	 * @return void
	 * @throws Alma_Api_Client_Exception Issue with the api.
	 */
	protected function init_alma_client() {
		try {
			// We try to get the client.
			$this->alma_settings->get_alma_client();
		} catch ( \Exception $e ) {
			$message = sprintf(
			// translators: %1$s: Admin settings url, %2$s: Admin logs url.
				__( 'Error while initializing Alma API client.<br><a href="%1$s">Activate debug mode</a> and <a href="%2$s">check logs</a> for more details.', 'alma-gateway-for-woocommerce' ),
				esc_url( Alma_Assets_Helper::get_admin_setting_url() ),
				esc_url( Alma_Assets_Helper::get_admin_logs_url() )
			);

			alma_plugin()->admin_notices->add_admin_notice( 'client_keys_build', 'notice notice-error', $message );

			throw new Alma_Api_Client_Exception( $message );
		}
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$form = new Alma_Form_Helper();

		$this->form_fields = $form->init_form_fields( $this->show_alma_fee_plans );
		$this->form_fields = apply_filters( 'wc_offline_form_fields', $this->form_fields ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}
}
