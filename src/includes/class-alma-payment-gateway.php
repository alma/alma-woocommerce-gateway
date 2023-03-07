<?php
/**
 * Alma_Payment_Gateway.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Helpers\Alma_Gateway_Helper;
use Alma\Woocommerce\Helpers\Alma_Checkout_Helper;
use Alma\Woocommerce\Helpers\Alma_General_Helper;
use Alma\Woocommerce\Helpers\Alma_Assets_Helper;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;
use Alma\Woocommerce\Exceptions\Alma_No_Credentials_Exception;
use Alma\Woocommerce\Admin\Helpers\Alma_General_Helper as Alma_Admin_General_Helper;
use Alma\Woocommerce\Admin\Helpers\Alma_Form_Helper;
use Alma\Woocommerce\Exceptions\Alma_Api_Client_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Merchants_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Plans_Exception;

/**
 * Alma_Payment_Gateway
 */
class Alma_Payment_Gateway extends \WC_Payment_Gateway {

	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	public $logger;

	/**
	 *  Show alma fee plan fields.
	 *
	 * @var bool
	 */
	public $show_alma_fee_plans = false;

	/**
	 * The settings.
	 *
	 * @var Alma_Settings
	 */
	public $alma_settings;

	/**
	 *  The gateway helper.
	 *
	 * @var Alma_Gateway_Helper
	 */
	public $gateway_helper;

	/**
	 *  The checkout helper.
	 *
	 * @var Alma_Checkout_Helper
	 */
	public $checkout_helper;

	/**
	 * The general helper.
	 *
	 * @var Alma_General_Helper
	 */
	public $general_helper;

	/**
	 *  The assets helper.
	 *
	 * @var Alma_Assets_Helper
	 */
	public $scripts_helper;

	/**
	 * The plan builder.
	 *
	 * @var Alma_Plan_Builder
	 */
	public $plan_builder;


	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = Alma_Constants_Helper::GATEWAY_ID;
		$this->has_fields         = true;
		$this->method_title       = __( 'Payment in instalments and deferred with Alma - 2x 3x 4x, D+15 or D+30', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );
		$this->logger             = new Alma_Logger();
		$this->alma_settings      = new Alma_Settings();
		$this->checkout_helper    = new Alma_Checkout_Helper();
		$this->gateway_helper     = new Alma_Gateway_Helper();
		$this->general_helper     = new Alma_General_Helper();
		$this->scripts_helper     = new Alma_Assets_Helper();
		$this->plan_builder       = new Alma_Plan_Builder();

		$this->check_activation();

		$this->check_alma_keys( false );
		$this->add_filters();
		$this->add_actions();
		$this->init_admin_form();
	}

		/**
		 * Return the name of the option in the WP DB.
		 *
		 * @since 2.6.0
		 * @return string
		 */
	public function get_option_key() {
		return Alma_Settings::OPTIONS_KEY;
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
	 * Get option from DB.
	 *
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 * This is overridden so that values saved in cents in the DB can be shown in euros to the user.
	 *
	 * @param string $key Option key.
	 * @param mixed  $empty_value Value when empty.
	 *
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		$option = parent::get_option( $key, $empty_value );

		if ( Alma_Tools_Helper::is_amount_plan_key( $key ) ) {
			return strval( Alma_Tools_Helper::alma_price_from_cents( $option ) );
		}

		return $option;
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
		add_filter( 'woocommerce_gateway_description', array( $this->gateway_helper, 'woocommerce_gateway_description' ), 10, 2 );
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
			&& Alma_Constants_Helper::GATEWAY_ID == $section
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
	 * @return void
	 * @throws Alma_Api_Client_Exception Client Api exception.
	 * @throws Alma_Api_Merchants_Exception Merchant Api exception.
	 * @throws Alma_Api_Plans_Exception  Plans Api exception.
	 * @throws Alma_No_Credentials_Exception No credentials Api excetption.
	 */
	public function manage_credentials() {
		$this->check_alma_keys();
		$this->init_alma_client();

		// We store the old merchant id.
		$old_merchant_id = $this->alma_settings->get_active_merchant_id();

		$this->init_alma_merchant();

		// If the merchant_id has changed we need to update it in the database.
		$merchant_id = $this->alma_settings->get_active_merchant_id();

		if ( $merchant_id !== $old_merchant_id ) {
			$this->alma_settings->settings[ $this->alma_settings->environment . '_merchant_id' ] = $merchant_id;
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
	 * Retrieve the merchant
	 *
	 * @return void
	 * @throws Alma_Api_Merchants_Exception Api error.
	 */
	protected function init_alma_merchant() {
		try {
			// We try to get the merchants.
			$this->alma_settings->get_alma_merchant_id();
		} catch ( \Exception $e ) {
			alma_plugin()->admin_notices->add_admin_notice( 'error_keys', 'notice notice-error', $e->getMessage(), true );
			throw new Alma_Api_Merchants_Exception( $e );
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

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 */
	public function get_icon() {
		return Alma_Assets_Helper::get_icon( $this->get_title(), $this->id );
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array The result.
	 */
	public function process_payment( $order_id ) {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );

		try {
			// We ignore the nonce verification because process_payment is called after validate_fields.
			$fee_plan_definition = $this->alma_settings->get_fee_plan_definition( $_POST[ Alma_Constants_Helper::ALMA_FEE_PLAN ] ); // phpcs:ignore WordPress.Security.NonceVerification

			$payment = $this->alma_settings->create_payments( $order_id, $fee_plan_definition );

			// Redirect user to our payment page.
			return array(
				'result'   => Alma_Constants_Helper::SUCCESS,
				'redirect' => $payment->url,
			);
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );
			wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );
			return array( 'result' => Alma_Constants_Helper::ERROR );
		}
	}

	/**
	 * Validate payment fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$error_msg = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );

		$alma_fee_plan = $this->checkout_helper->get_chosen_alma_fee_plan( $this->id );

		if ( ! $alma_fee_plan ) {
			wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );

			return false;
		}

		$is_alma_payment = $this->checkout_helper->is_alma_payment_method( $this->id );

		if ( ! $is_alma_payment ) {
			wc_add_notice( $error_msg, Alma_Constants_Helper::ERROR );
			return false;
		}
		$allowed_values = array_map( 'strval', $this->alma_settings->get_eligible_plans_keys_for_cart() );

		if ( ! in_array( $alma_fee_plan, $allowed_values, true ) ) {
			wc_add_notice( '<strong>Fee plan</strong> is invalid.', Alma_Constants_Helper::ERROR );

			return false;
		}

		return true;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool Is available.
	 */
	public function is_available() {
		$tools = new Alma_Tools_Helper();

		if (
			! $this->alma_settings->is_allowed_to_see_alma( wp_get_current_user() )
			|| is_admin()
			|| ! $tools->check_currency()
		) {
			return false;
		}

		if (
			wc()->cart === null
			|| ! is_checkout()
		) {
			return parent::is_available();
		}

		if (
			! $this->gateway_helper->is_there_eligibility_in_cart()
			|| $this->gateway_helper->cart_contains_excluded_category()
		) {
			return false;
		}

		return $this->alma_settings->is_cart_eligible() && parent::is_available();
	}

	/**
	 * Generate a select `alma_fee` Input HTML (based on `select_alma_fee_plan` field definition).
	 * Warning: use only one `select_alma_fee_plan` type in your form (script & css are inlined here)
	 *
	 * @param mixed $key as field key.
	 * @param mixed $data as field configuration.
	 *
	 * @return string
	 * @see WC_Settings_API::generate_settings_html() that calls dynamically generate_<field_type>_html
	 * @noinspection PhpUnused
	 */
	public function generate_select_alma_fee_plan_html( $key, $data ) {
		?>
		<script>
			var select_alma_fee_plans_id = select_alma_fee_plans_id || '<?php echo esc_attr( $this->get_field_key( $key ) ); ?>';
		</script>
		<style>
			.alma_option_enabled::after {
				content: ' (<?php echo esc_attr__( 'enabled', 'alma-gateway-for-woocommerce' ); ?>)';
			}

			.alma_option_disabled::after {
				content: ' (<?php echo esc_attr__( 'disabled', 'alma-gateway-for-woocommerce' ); ?>)';
			}

		</style>
		<?php
		return parent::generate_select_html( $key, $data );
	}

	/**
	 * Override WC_Settings_API Generate Title HTML.
	 * add css, description_css, table_css, description_class & table_class definitions.
	 *
	 * @param mixed $key as string form field key.
	 * @param mixed $data as field definition array.
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function generate_title_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'class'             => '',
			'css'               => '',
			'table_class'       => '',
			'table_css'         => '',
			'description_class' => '',
			'description_css'   => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		</table>
		<h3 class="wc-settings-sub-title <?php echo esc_attr( $data['class'] ); ?>"
			style="<?php echo esc_attr( $data['css'] ); ?>"
			id="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></h3>
		<?php if ( ! empty( $data['description'] ) ) : ?>
			<div class="<?php echo esc_attr( $data['description_class'] ); ?>"
				style="<?php echo esc_attr( $data['description_css'] ); ?>"><?php echo wp_kses_post( $data['description'] ); ?></div>
		<?php endif; ?>
	<table class="form-table <?php echo esc_attr( $data['table_class'] ); ?>"
		style="<?php echo esc_attr( $data['table_css'] ); ?>">
		<?php

		return ob_get_clean();
	}

	/**
	 * Processes and saves options.
	 */
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		// If the mode has changed, or the keys.
		$this->clean_credentials( $post_data );

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->alma_settings->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( \Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		$this->convert_amounts_to_cents();

		update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->alma_settings->settings ), 'yes' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$this->alma_settings->load_settings();

		try {
			$this->manage_credentials();
			$this->alma_settings->load_settings();

		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage() );
		}
	}

	/**
	 * Clean the credentials
	 *
	 * @param array $post_data  The data.
	 * @return void
	 */
	public function clean_credentials( $post_data ) {
		if (
			(
				$this->alma_settings->settings['live_api_key'] !== $post_data['woocommerce_alma_live_api_key']
				&& 'live' === $post_data['woocommerce_alma_environment']
			)
			|| (
				$this->alma_settings->settings['test_api_key'] !== $post_data['woocommerce_alma_test_api_key']
				&& 'test' === $post_data['woocommerce_alma_environment']
			)
			|| $this->alma_settings->settings['environment'] !== $post_data['woocommerce_alma_environment']
		) {
			$this->reset_plans();
		}
	}

	/**
	 * Reset min and max amount for all plans.
	 *
	 * @return void
	 */
	public function reset_plans() {
		foreach ( array_keys( $this->alma_settings->settings ) as $key ) {
			if ( Alma_Tools_Helper::is_amount_plan_key( $key ) ) {
				$this->alma_settings->settings[ $key ] = null;
			}
		}

		$this->alma_settings->settings['allowed_fee_plans'] = null;
		$this->alma_settings->settings['live_merchant_id']  = null;
		$this->alma_settings->settings['test_merchant_id']  = null;
	}

	/**
	 * After settings have been updated, override min/max amounts to convert them to cents.
	 */
	protected function convert_amounts_to_cents() {
		$post_data = $this->get_post_data();
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( Alma_Tools_Helper::is_amount_plan_key( $key ) ) {
				try {
					$amount                                = $this->get_field_value( $key, $field, $post_data );
					$this->alma_settings->settings[ $key ] = Alma_Tools_Helper::alma_price_to_cents( $amount );
				} catch ( \Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
	}

	/**
	 * Custom payment fields for a payment gateway.
	 * (We have three payment gateways : "alma", "alma_pay_later", and "alma_pnx_plus_4")
	 */
	public function payment_fields() {
		echo wp_kses_post( $this->get_description() );

		$this->checkout_helper->render_nonce_field( $this->id );

		// We get the eligibilites.
		$eligibilities  = $this->alma_settings->get_cart_eligibilities();
		$eligible_plans = $this->alma_settings->get_eligible_plans_keys_for_cart( $eligibilities );
		$default_plan   = $this->gateway_helper->get_default_plan( $eligible_plans );

		$this->plan_builder->render_checkout_fields( $eligibilities, $eligible_plans, $default_plan );
	}

		/**
		 * Generates a html `i18n` Input HTML.
		 *
		 * @param string         $key The field name.
		 * @param array|string[] $data The configuration for this field.
		 *
		 * @return false|string
		 * @see WC_Settings_API::generate_text_html()
		 * @noinspection PhpUnused
		 */
	public function generate_text_alma_i18n_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'lang_list'         => array(),
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top" class="alma-i18n-parent" style="display:none;">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
					</legend>
					<input class="input-text regular-input alma-i18n <?php echo esc_attr( $data['class'] ); ?>"
						type="text" name="<?php echo esc_attr( $field_key ); ?>"
						id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>"
						value="<?php echo esc_attr( $this->get_option( $key ) ); ?>"
						placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'] ); ?> <?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?> />
					<select class="list_lang_title" style="width:auto;margin-left:10px;line-height:28px;">
						<?php
						foreach ( $data['lang_list'] as $code => $label ) {
							$selected = Alma_Admin_General_Helper::is_lang_selected( esc_attr( $code ) ) ? 'selected="selected"' : '';
							print '<option value="' . esc_attr( $code ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $label ) . '</option>';
						}
						?>
					</select>
					<?php echo $this->get_description_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


} // end \Alma_Pay_Gateway class
