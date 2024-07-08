<?php
/**
 * AlmaPaymentGateway.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Gateways
 * @namespace Alma\Woocommerce\Gateways
 */

namespace Alma\Woocommerce\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Admin\Helpers\CheckLegalHelper;
use Alma\Woocommerce\Admin\Helpers\FormHelper;
use Alma\Woocommerce\Admin\Helpers\GeneralHelper as AdminGeneralHelper;
use Alma\Woocommerce\Admin\Helpers\ShareOfCheckoutHelper;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\GatewayHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\PlanHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Exceptions\ApiClientException;
use Alma\Woocommerce\Exceptions\ApiMerchantsException;
use Alma\Woocommerce\Exceptions\ApiPlansException;
use Alma\Woocommerce\Exceptions\NoCredentialsException;
use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\PluginFactory;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\CheckoutHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\EncryptorHelper;
use Alma\Woocommerce\Helpers\GatewayHelper;
use Alma\Woocommerce\Helpers\OrderHelper;
use Alma\Woocommerce\Helpers\PaymentHelper;
use Alma\Woocommerce\Helpers\PlanHelper;
use Alma\Woocommerce\Helpers\PluginHelper;
use Alma\Woocommerce\Helpers\SettingsHelper;
use Alma\Woocommerce\Helpers\TemplateLoaderHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;

/**
 * AlmaPaymentGateway
 */
class AlmaPaymentGateway extends \WC_Payment_Gateway {


	/**
	 * The logger.
	 *
	 * @var AlmaLogger
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
	 * @var AlmaSettings
	 */
	public $alma_settings;

	/**
	 *  The gateway helper.
	 *
	 * @var GatewayHelper
	 */
	public $gateway_helper;

	/**
	 *  The checkout helper.
	 *
	 * @var CheckoutHelper
	 */
	public $checkout_helper;

	/**
	 *  The assets helper.
	 *
	 * @var AssetsHelper
	 */
	public $scripts_helper;

	/**
	 *  The encryptor.
	 *
	 * @var EncryptorHelper
	 */
	protected $encryption_helper;

	/**
	 * The legal helper.
	 *
	 * @var CheckLegalHelper
	 */
	protected $check_legal_helper;

	/**
	 * Helper global.
	 *
	 * @var ToolsHelper
	 */
	protected $tool_helper;

	/**
	 * The payment helper.
	 *
	 * @var PaymentHelper
	 */
	protected $alma_payment_helper;

	/**
	 * The order helper.
	 *
	 * @var OrderHelper
	 */
	public $order_helper;

	/**
	 * The template loader.
	 *
	 * @var TemplateLoaderHelper
	 */
	public $template_loader;

	/**
	 * The share of checkout helper.
	 *
	 * @var ShareOfCheckoutHelper
	 */
	public $soc_helper;

	/**
	 * The plugin helper.
	 *
	 * @var PluginHelper
	 */
	public $plugin_helper;

	/**
	 * The cart helper.
	 *
	 * @var CartHelper
	 */
	protected $cart_helper;

	/**
	 * The settings helper.
	 *
	 * @var SettingsHelper
	 */
	protected $settings_helper;


	/**
	 * The asset helper.
	 *
	 * @var AssetsHelper
	 */
	protected $asset_helper;


	/**
	 * The plugin factory.
	 *
	 * @var PluginFactory
	 */
	protected $plugin_factory;

	/**
	 * The cart factory.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;

	/**
	 * Alma plan builder.
	 *
	 * @var PlanHelper
	 */
	protected $alma_plan_helper;

	/**
	 * Construct.
	 *
	 * @param bool $check_basics Check the basics requirement.
	 * @throws NoCredentialsException The exception.
	 */
	public function __construct( $check_basics = true ) {
		$this->logger              = new AlmaLogger();
		$this->alma_settings       = new AlmaSettings();
		$this->check_legal_helper  = new CheckLegalHelper();
		$this->checkout_helper     = new CheckoutHelper();
		$gateway_helper_builder    = new GatewayHelperBuilder();
		$this->gateway_helper      = $gateway_helper_builder->get_instance();
		$this->scripts_helper      = new AssetsHelper();
		$this->encryption_helper   = new EncryptorHelper();
		$tools_helper_builder      = new ToolsHelperBuilder();
		$this->tool_helper         = $tools_helper_builder->get_instance();
		$this->alma_payment_helper = new PaymentHelper();
		$this->order_helper        = new OrderHelper();
		$this->template_loader     = new TemplateLoaderHelper();
		$this->soc_helper          = new ShareOfCheckoutHelper();
		$this->plugin_helper       = new PluginHelper();
		$this->asset_helper        = new AssetsHelper();
		$alma_plan_builder         = new PlanHelperBuilder();
		$this->alma_plan_helper    = $alma_plan_builder->get_instance();

		$this->plugin_factory = new PluginFactory();
		$this->cart_factory   = new CartFactory();

		$settings_helper_builder = new SettingsHelperBuilder();
		$this->settings_helper   = $settings_helper_builder->get_instance();

		$cart_helper_builder = new CartHelperBuilder();
		$this->cart_helper   = $cart_helper_builder->get_instance();

		$this->id                 = $this->get_gateway_id();
		$this->method_title       = __( 'Payment in instalments and deferred with Alma - 2x 3x 4x', 'alma-gateway-for-woocommerce' );
		$this->method_description = __( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.', 'alma-gateway-for-woocommerce' );

		if ( $check_basics ) {
			$this->check_activation();

			$this->settings_helper->check_alma_keys( $this->alma_settings->has_keys(), false );
			$this->add_filters();
			$this->gateway_helper->add_actions();
			$this->init_admin_form();
			$this->check_legal_helper->check_share_checkout();
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
		add_filter( 'allowed_redirect_hosts', array( $this->asset_helper, 'alma_domains_whitelist' ) );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_option_key() {
		return AlmaSettings::OPTIONS_KEY;
	}

	/**
	 * Check plugin activation.
	 *
	 * @return void
	 */
	public function check_activation() {
		$enabled = $this->get_option( 'enabled', 'no' );

		if ( ! ToolsHelper::alma_string_to_bool( $enabled ) ) {
			$message = sprintf(
				// translators: %s: Admin settings url.
				__( "Thanks for installing Alma! Start by <a href='%s'>activating Alma's payment method</a>, then set it up to get started.", 'alma-gateway-for-woocommerce' ),
				esc_url( $this->asset_helper->get_admin_setting_url( false ) )
			);
			$this->plugin_factory->add_admin_notice( 'no_alma_enabled', 'notice notice-warning', $message );
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

		if ( $this->tool_helper->is_amount_plan_key( $key ) ) {
			return strval( $this->tool_helper->alma_price_from_cents( $option ) );
		}

		return $option;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool Is available.
	 */
	public function is_available() {
		global $wp;

		if (
			! $this->alma_settings->is_allowed_to_see_alma( wp_get_current_user() )
			|| is_admin()
			|| ! $this->tool_helper->check_currency()
			|| is_wc_endpoint_url( 'order-pay' )
			|| is_wc_endpoint_url( 'cart' )
			|| ! empty( $wp->query_vars['order-pay'] )
			|| is_cart()
		) {
			return false;
		}

		if (
			$this->cart_factory->get_cart() === null
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

		$eligibilities  = $this->cart_helper->get_cart_eligibilities();
		$eligible_plans = $this->cart_helper->get_eligible_plans_keys_for_cart( $eligibilities );
		$eligible_plans = $this->alma_plan_helper->order_plans( $eligible_plans );

		$is_eligible = false;

		if ( ! empty( $eligible_plans[ $this->id ] ) ) {
			$is_eligible = true;
		}

		return $is_eligible && parent::is_available();
	}


	/**
	 *  Initialize the admin form.
	 *
	 * @return void
	 */
	public function init_admin_form() {
		list($tab, $section) = $this->plugin_helper->get_tab_and_section();

		if (
			is_admin()
			&& 'checkout' === $tab
			&& in_array( $section, ConstantsHelper::$gateways_ids, true )
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
	 *  Manage the credentials, check the clients and manage plans.
	 *
	 * @return void
	 * @throws ApiClientException Client Api exception.
	 * @throws ApiMerchantsException Merchant Api exception.
	 * @throws ApiPlansException  Plans Api exception.
	 * @throws NoCredentialsException No credentials Api excetption.
	 */
	public function manage_credentials() {
		$this->settings_helper->check_alma_keys( $this->alma_settings->has_keys() );

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
	 * @throws ApiClientException Issue with the api.
	 */
	protected function init_alma_client() {
		try {
			// We try to get the client.
			$this->alma_settings->get_alma_client();
		} catch ( \Exception $e ) {
			$message = sprintf(
			// translators: %1$s: Admin settings url, %2$s: Admin logs url.
				__( 'Error while initializing Alma API client.<br><a href="%1$s">Activate debug mode</a> and <a href="%2$s">check logs</a> for more details.', 'alma-gateway-for-woocommerce' ),
				esc_url( $this->asset_helper->get_admin_setting_url() ),
				esc_url( AssetsHelper::get_admin_logs_url() )
			);

			$this->plugin_factory->add_admin_notice( 'client_keys_build', 'notice notice-error', $message );

			throw new ApiClientException( $message );
		}
	}

	/**
	 * Retrieve the merchant
	 *
	 * @return void
	 * @throws ApiMerchantsException Api error.
	 */
	protected function init_alma_merchant() {
		try {
			// We try to get the merchants.
			$this->alma_settings->get_alma_merchant_id();
		} catch ( \Exception $e ) {
			$this->plugin_factory->add_admin_notice( 'error_keys', 'notice notice-error', $e->getMessage(), true );
			throw new ApiMerchantsException( $e );
		}

	}

	/**
	 * Initialize Gateway AlmaSettings Form Fields
	 */
	public function init_form_fields() {
		$form = new FormHelper();

		$this->form_fields = $form->init_form_fields( $this->show_alma_fee_plans );
		$this->form_fields = apply_filters( 'wc_offline_form_fields', $this->form_fields ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 */
	public function get_icon() {
		return AssetsHelper::get_icon( $this->get_title(), $this->id, ConstantsHelper::ALMA_SHORT_LOGO_PATH );
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';

		if ( ! empty( $this->settings['test_api_key'] ) ) {
			$this->settings['test_api_key'] = $this->encryption_helper->decrypt( $this->settings['test_api_key'] );
		}

		if ( ! empty( $this->settings['live_api_key'] ) ) {
			$this->settings['live_api_key'] = $this->encryption_helper->decrypt( $this->settings['live_api_key'] );
		}
	}

	/**
	 * Processes and saves options.
	 */
	public function process_admin_options() {
		$this->init_settings();
		set_transient( 'alma-admin-soc-panel', true, 5 );

		$post_data = $this->get_post_data();

		// We encrypt the keys.
		$post_data = $this->encrypt_keys( $post_data );

		// Manage the soc changes.
		if ( $this->soc_helper->soc_has_changed( $post_data ) ) {
			$this->alma_settings->settings = $this->soc_helper->process_checkout_legal( $post_data, $this->alma_settings->settings );
		}

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
	 * Encrypt the api keys.
	 *
	 * @param array $post_data The form data.
	 * @return array The form data.
	 */
	protected function encrypt_keys( $post_data ) {
		if ( ! empty( $post_data['woocommerce_alma_live_api_key'] ) ) {
			$post_data['woocommerce_alma_live_api_key'] = $this->encryption_helper->encrypt( $post_data['woocommerce_alma_live_api_key'] );
		}

		if ( ! empty( $post_data['woocommerce_alma_test_api_key'] ) ) {
			$post_data['woocommerce_alma_test_api_key'] = $this->encryption_helper->encrypt( $post_data['woocommerce_alma_test_api_key'] );
		}

		return $post_data;
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
			$this->alma_settings->settings = $this->settings_helper->reset_plans( $this->alma_settings->settings );
		}
	}

	/**
	 * After settings have been updated, override min/max amounts to convert them to cents.
	 */
	protected function convert_amounts_to_cents() {
		$post_data = $this->get_post_data();

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( $this->tool_helper->is_amount_plan_key( $key ) ) {
				try {
					$amount                                = $this->get_field_value( $key, $field, $post_data );
					$this->alma_settings->settings[ $key ] = $this->tool_helper->alma_price_to_cents( $amount );
				} catch ( \Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
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
		$admin_general_helper = new AdminGeneralHelper();

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
				<label for="<?php echo esc_attr( $field_key ); ?>">
					<?php echo wp_kses_post( $data['title'] ); ?>
					<?php echo $this->get_tooltip_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>
							<?php echo wp_kses_post( $data['title'] ); ?>
						</span>
					</legend>
					<input class="input-text regular-input alma-i18n <?php echo esc_attr( $data['class'] ); ?>" type="text"
						name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>"
						style="<?php echo esc_attr( $data['css'] ); ?>"
						value="<?php echo esc_attr( $this->get_option( $key ) ); ?>"
						placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'] ); ?>
						<?php echo $this->get_custom_attribute_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput ?> />
					<select class="list_lang_title" style="width:auto;margin-left:10px;line-height:28px;">
						<?php
						foreach ( $data['lang_list'] as $code => $label ) {
							$selected = $admin_general_helper->is_lang_selected( esc_attr( $code ) ) ? 'selected="selected"' : '';
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
			$wc_order = $this->order_helper->get_order( $order_id );

			// We ignore the nonce verification because process_payment is called after validate_fields.
			$fee_plan = $this->alma_settings->build_fee_plan( $_POST[ ConstantsHelper::ALMA_FEE_PLAN ] ); // phpcs:ignore WordPress.Security.NonceVerification
			$payment  = $this->alma_payment_helper->create_payments( $wc_order, $fee_plan );

			// Redirect user to our payment page.
			return array(
				'result'   => ConstantsHelper::SUCCESS,
				'redirect' => $payment->url,
			);
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );
			wc_add_notice( $error_msg, ConstantsHelper::ERROR );
			return array( 'result' => ConstantsHelper::ERROR );
		}
	}

	/**
	 * Validate payment fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$error_msg     = __( 'There was an error processing your payment.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' );
		$alma_fee_plan = $this->checkout_helper->get_chosen_alma_fee_plan( $this->id );

		if ( ! $alma_fee_plan ) {
			wc_add_notice( $error_msg, ConstantsHelper::ERROR );

			return false;
		}

		$is_alma_payment = $this->checkout_helper->is_alma_payment_method( $this->id );

		if ( ! $is_alma_payment ) {
			wc_add_notice( $error_msg, ConstantsHelper::ERROR );
			return false;
		}
		$allowed_values = $this->cart_helper->get_eligible_plans_keys_for_cart();
		$allowed_values = $this->alma_plan_helper->order_plans( $allowed_values );

		if ( ! in_array( $alma_fee_plan, $allowed_values[ $this->id ], true ) ) {
			$this->logger->error(
				sprintf(
					'Fee plan is invalid : %s, allowed values : %s, gateway id : %s',
					$alma_fee_plan,
					wp_json_encode( $allowed_values ),
					$this->id
				)
			);
			wc_add_notice( '<strong>Fee plan</strong> is invalid.', ConstantsHelper::ERROR );

			return false;
		}

		return true;
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
			style="<?php echo esc_attr( $data['css'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>">
			<?php echo wp_kses_post( $data['title'] ); ?>
		</h3>
		<?php if ( ! empty( $data['description'] ) ) : ?>
			<div class="<?php echo esc_attr( $data['description_class'] ); ?>"
				style="<?php echo esc_attr( $data['description_css'] ); ?>">
				<?php echo wp_kses_post( $data['description'] ); ?>
			</div>
		<?php endif; ?>
		<table class="form-table <?php echo esc_attr( $data['table_class'] ); ?>"
			style="<?php echo esc_attr( $data['table_css'] ); ?>">
			<?php

			return ob_get_clean();
	}


	/**
	 * Has fields.
	 *
	 * @return true
	 */
	public function has_fields() {
		return true;
	}

}
