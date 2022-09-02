<?php
/**
 * Alma payments plugin for WooCommerce
 *
 * @package Alma_WooCommerce_Gateway
 * @noinspection HtmlUnknownTarget
 */

use Alma\API\Client;
use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Merchant;
use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Plugin
 */
class Alma_WC_Plugin {
	/**
	 * Instance of Alma_Settings.
	 *
	 * @var Alma_WC_Settings
	 */
	public $settings;
	/**
	 * Eligibilities
	 *
	 * @var array<int,Eligibility>|null
	 */
	private $eligibilities;
	/**
	 * Flag to indicate the plugin has been bootstrapped.
	 *
	 * @var bool
	 */
	private $bootstrapped = false;
	/**
	 * Instance of Alma Api client.
	 *
	 * @var Client
	 */
	private $alma_client;

	/**
	 * Instance of WC_Logger.
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * Instance of current Merchant (if any)
	 *
	 * @var Merchant|null
	 */
	private $alma_merchant;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();
		$this->self_update();
	}

	/**
	 * Update plugin to the latest version.
	 *
	 * @return void
	 */
	private function self_update() {
		$db_version = get_option( 'alma_version' );
		if ( ! $db_version ) {
			update_option( 'alma_version', ALMA_WC_VERSION );

			return;
		}
		if ( version_compare( ALMA_WC_VERSION, $db_version, '>' ) ) {
			update_option( 'alma_version', ALMA_WC_VERSION );
		}
	}

	/**
	 * Try running.
	 *
	 * @return void
	 */
	public function try_running() {
		add_action(
			Alma_WC_Webhooks::action_for( Alma_WC_Webhooks::CUSTOMER_RETURN ),
			array(
				$this,
				'handle_customer_return',
			)
		);

		add_action(
			Alma_WC_Webhooks::action_for( Alma_WC_Webhooks::IPN_CALLBACK ),
			array(
				$this,
				'handle_ipn_callback',
			)
		);

		add_action( 'init', array( $this, 'bootstrap' ) );
		add_action( 'init', array( $this, 'check_share_checkout' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'alma_domains_whitelist' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( ALMA_WC_PLUGIN_FILE ), array(
			$this,
			'plugin_action_links'
		) );
		add_action( 'wp_ajax_alma_dismiss_notice_message', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'alma_admin_enqueue_scripts' ) );

		add_filter( 'woocommerce_gateway_title', array( $this, 'woocommerce_gateway_title' ), 10, 2 );
		add_filter( 'woocommerce_gateway_description', array( $this, 'woocommerce_gateway_description' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		$payment_upon_trigger_helper = new Alma_WC_Payment_Upon_Trigger();
		add_action( 'woocommerce_order_status_changed', array(
			$payment_upon_trigger_helper,
			'woocommerce_order_status_changed'
		), 10, 3 );

		// Launch the "share of checkout".
		$share_of_checkout = new Alma_WC_Share_Of_Checkout();
		$share_of_checkout->init();

		$refund = new Alma_WC_Refund();
		add_action( 'admin_init', array( $refund, 'admin_init' ), 10 );
	}

	/**
	 * Enqueue scripts needed into admin form
	 */
	public function alma_admin_enqueue_scripts() {
		wp_enqueue_style(
			'alma-admin-styles',
			alma_wc_plugin()->get_asset_url( 'css/alma-admin.css' ),
			array(),
			ALMA_WC_VERSION
		);

		wp_enqueue_script(
			'alma-admin-scripts',
			alma_wc_plugin()->get_asset_url( 'js/alma-admin.js' ),
			array( 'jquery-effects-highlight', 'jquery-ui-selectmenu' ),
			ALMA_WC_VERSION,
			true
		);
	}

	/**
	 * Get asset url.
	 *
	 * @param string $path Path to asset relative to the plugin's assets directory.
	 *
	 * @return string URL to given asset
	 */
	public function get_asset_url( $path ) {
		return ALMA_WC_PLUGIN_URL . 'assets/' . $path;
	}

	/**
	 * Bootstrap
	 *
	 * @return void
	 *
	 * @throws \Exception Exception.
	 */
	public function bootstrap() {
		try {
			if ( $this->bootstrapped ) {
				throw new Exception( __( 'WooCommerce Gateway Alma plugin can only be bootstrapped once', 'alma-gateway-for-woocommerce' ) );
			}

			$this->load_plugin_textdomain();

			delete_option( 'alma_bootstrap_warning_message' );

			$this->check_dependencies();

			$this->settings = new Alma_WC_Settings();

			$this->run();

			if ( is_admin() ) {
				// Defer settings check to after potential settings update.
				update_option( 'alma_warnings_handled', false );
				$this->settings->save();
				add_action( 'admin_notices', array( $this, 'check_settings' ) );
			}
		} catch ( Exception $e ) {
			$this->logger->error( 'Bootstrap error: ' . $e->getMessage() );
			$this->handle_settings_exception( $e );
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'alma-gateway-for-woocommerce', false, plugin_basename( ALMA_WC_PLUGIN_PATH ) . '/languages' );
	}

	/**
	 * Check dependencies.
	 *
	 * @throws Exception Exception.
	 */
	protected function check_dependencies() {
		if ( ! function_exists( 'WC' ) ) {
			throw new Exception( __( 'Alma requires WooCommerce to be activated', 'alma-gateway-for-woocommerce' ) );
		}

		if ( version_compare( wc()->version, '2.6', '<' ) ) {
			throw new Exception( __( 'Alma requires WooCommerce version 2.6 or greater', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'curl_init' ) ) {
			throw new Exception( __( 'Alma requires the cURL PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		if ( ! function_exists( 'json_decode' ) ) {
			throw new Exception( __( 'Alma requires the JSON PHP extension to be installed on your server', 'alma-gateway-for-woocommerce' ) );
		}

		$openssl_warning = __( 'Alma requires OpenSSL >= 1.0.1 to be installed on your server', 'alma-gateway-for-woocommerce' );
		if ( ! defined( 'OPENSSL_VERSION_TEXT' ) ) {
			throw new Exception( $openssl_warning );
		}

		preg_match( '/^(?:Libre|Open)SSL ([\d.]+)/', OPENSSL_VERSION_TEXT, $matches );
		if ( empty( $matches[1] ) ) {
			throw new Exception( $openssl_warning );
		}

		if ( ! version_compare( $matches[1], '1.0.1', '>=' ) ) {
			throw new Exception( $openssl_warning );
		}
	}

	/**
	 * Run the plugin.
	 */
	private function run() {

		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_gateway' ) );

		if ( ! $this->settings->is_enabled() ) {
			return;
		}

		// Don't advertise our payment gateway if we're in test mode and current user is not an admin.
		if ( ! $this->is_allowed_to_see_alma( wp_get_current_user() ) ) {
			$this->logger->info( 'Not displaying Alma in Test mode to non-admin user' );

			return;
		}

		$this->init_widget_handlers();
	}

	/**
	 * Is Alma available for this user ?
	 *
	 * @param WP_User $user The user roles which to test.
	 *
	 * @return bool
	 */
	public function is_allowed_to_see_alma( WP_User $user ) {
		return in_array( 'administrator', $user->roles, true ) || 'live' === alma_wc_plugin()->settings->get_environment();
	}

	/**
	 * Init the alma widget handlers :
	 * - hooked on WooCommerce Cart & Product actions
	 * - AND add associated shortcodes
	 */
	private function init_widget_handlers() {
		$shortcodes = new Alma_WC_Shortcodes();

		$cart_handler = new Alma_WC_Cart_Handler();
		$shortcodes->init_cart_widget_shortcode( $cart_handler );

		$product_handler = new Alma_WC_Product_Handler();
		$shortcodes->init_product_widget_shortcode( $product_handler );
	}

	/**
	 * Handle settings exception.
	 *
	 * @param \Exception $exception Exception.
	 *
	 * @return void
	 */
	public function handle_settings_exception( $exception ) {
		if ( get_option( 'alma_warnings_handled' ) ) {
			return;
		}

		delete_option( 'alma_bootstrap_warning_message_dismissed' );
		update_option( 'alma_bootstrap_warning_message', $exception->getMessage() );
		$this->logger->warning( 'Bootstrap warning: ' . $exception->getMessage() );

		add_action( 'admin_notices', array( $this, 'show_settings_warning' ) );

		update_option( 'alma_warnings_handled', true );
	}

	/**
	 * Show settings warning.
	 *
	 * @return void
	 */
	public function show_settings_warning() {
		$message = get_option( 'alma_bootstrap_warning_message', '' );
		if ( ! empty( $message ) && ! get_option( 'alma_bootstrap_warning_message_dismissed' ) ) {
			?>
            <div class="notice notice-warning is-dismissible alma-dismiss-bootstrap-warning-message">
                <p>
                    <strong><?php echo wp_kses_post( $message ); ?></strong>
                </p>
            </div>
            <script>
                (function ($) {
                    $('.alma-dismiss-bootstrap-warning-message').on('click', '.notice-dismiss', function () {
                        jQuery.post("<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>", {
                            action: "alma_dismiss_notice_message",
                            dismiss_action: "alma_dismiss_bootstrap_warning_message",
                            nonce: "<?php echo esc_js( wp_create_nonce( 'alma_dismiss_notice' ) ); ?>"
                        });
                    });
                })(jQuery);
            </script>
			<?php
		}
	}

	/**
	 * Check settings.
	 *
	 * @return void
	 * @see self::force_check_settings()
	 */
	public function check_settings() {
		if ( ( $this->settings->fully_configured || get_option( 'alma_warnings_handled' ) ) ) {
			return;
		}

		$this->force_check_settings();
	}

	/**
	 * Force Check settings.
	 *
	 * @return void
	 */
	public function force_check_settings() {
		try {
			update_option( 'alma_warnings_handled', false );
			$this->settings->fully_configured = false;

			$this->check_activation();
			$this->check_credentials();
			$this->check_merchant_status();

			$this->settings->fully_configured = true;

			$this->settings->save();
		} catch ( Exception $exception ) {
			$this->handle_settings_exception( $exception );
		}
	}

	/**
	 * Check activation.
	 *
	 * @return void
	 *
	 * @throws Exception Exception.
	 */
	public function check_activation() {
		$gateway = new Alma_WC_Payment_Gateway();
		$enabled = $gateway->get_option( 'enabled', 'no' );

		if ( ! alma_wc_string_to_bool( $enabled ) ) {
			throw new Exception(
				sprintf(
				// translators: %s: Admin settings url.
					__( "Thanks for installing Alma! Start by <a href='%s'>activating Alma's payment method</a>, then set it up to get started.", 'alma-gateway-for-woocommerce' ),
					esc_url( $this->get_admin_setting_url( false ) )
				)
			);
		}
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
		} else {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		}
	}

	/**
	 * Check that we have an API key.
	 * If not, it will throw an exception that will result in a prompt to configure the plugin
	 *
	 * @throws Exception Exception.
	 */
	public function check_credentials() {
		if ( $this->settings->need_api_key() ) {
			$settings_url = $this->get_admin_setting_url();
			throw new Exception(
				sprintf(
				// translators: %s: Admin settings url.
					__( 'Alma is almost ready. To get started, <a href="%s">fill in your API keys</a>.', 'alma-gateway-for-woocommerce' ),
					esc_url( $settings_url )
				)
			);
		}
	}

	/**
	 * Check merchant status.
	 *
	 * @throws Exception Exception.
	 */
	public function check_merchant_status() {
		$alma = $this->get_alma_client();

		$settings_url = $this->get_admin_setting_url();
		if ( ! $alma ) {
			throw new Exception(
				sprintf(
				// translators: %1$s: Admin settings url, %2$s: Admin logs url.
					__( 'Error while initializing Alma API client.<br><a href="%1$s">Activate debug mode</a> and <a href="%2$s">check logs</a> for more details.', 'alma-gateway-for-woocommerce' ),
					esc_url( $settings_url ),
					esc_url( $this->get_admin_logs_url() )
				)
			);
		}

		try {
			$merchant = $this->get_merchant();
		} catch ( RequestError $e ) {
			if ( $e->response && 401 === $e->response->responseCode ) {
				throw new Exception(
					sprintf(
					// translators: %s: Alma dashboard url.
						__( 'Could not connect to Alma using your API keys.<br>Please double check your keys on your <a href="%1$s" target="_blank">Alma dashboard</a>.', 'alma-gateway-for-woocommerce' ),
						$this->get_alma_dashboard_url( 'security' )
					)
				);
			} else {
				throw new Exception(
					sprintf(
					// translators: %s: Error message.
						__( 'Alma encountered an error when fetching merchant status: %s', 'alma-gateway-for-woocommerce' ),
						$e->getMessage()
					),
					$e->getCode(),
					$e
				);
			}
		}

		if ( ! $merchant->can_create_payments ) {
			throw new Exception(
				sprintf(
				// translators: %s: Alma dashboard url.
					__( 'Your Alma account needs to be activated before you can use Alma on your shop.<br>Go to your <a href="%1$s" target="_blank">Alma dashboard</a> to activate your account.<br><a href="%2$s">Refresh</a> the page when ready.', 'alma-gateway-for-woocommerce' ),
					$this->get_alma_dashboard_url( 'settings' ),
					esc_url( $settings_url )
				)
			);
		}
	}

	/**
	 * Get alma client.
	 *
	 * @return Client|null
	 */
	public function get_alma_client() {
		if ( ! $this->alma_client ) {
			$this->init_alma_client();
		}

		return $this->alma_client;
	}

	/**
	 * Init alma client.
	 *
	 * @return void
	 */
	private function init_alma_client() {
		try {
			$this->alma_client = new Client(
				$this->settings->get_active_api_key(),
				array(
					'mode'   => $this->settings->get_environment(),
					'logger' => $this->logger,
				)
			);

			$this->alma_client->addUserAgentComponent( 'WordPress', get_bloginfo( 'version' ) );
			$this->alma_client->addUserAgentComponent( 'WooCommerce', wc()->version );
			$this->alma_client->addUserAgentComponent( 'Alma for WooCommerce', ALMA_WC_VERSION );
		} catch ( \Exception $e ) {
			if ( $this->settings->is_logging_enabled() ) {
				$this->logger->log_stack_trace( 'Error creating Alma API client', $e );
			}
		}
	}

	/**
	 * Get admin logs url.
	 *
	 * @return string
	 */
	public function get_admin_logs_url() {
		return admin_url( 'admin.php?page=wc-status&tab=logs' );
	}

	/**
	 * Retrieve Merchant from Alma API
	 *
	 * @return Merchant|null
	 *
	 * @throws RequestError On API exception.
	 */
	public function get_merchant() {
		if ( $this->alma_merchant ) {
			return $this->alma_merchant;
		}
		$client = $this->get_alma_client();
		if ( ! $client ) {
			return null;
		}
		$this->alma_merchant = $client->merchants->me();

		return $this->alma_merchant;
	}

	/**
	 * Get Alma full URL depends on test or live mode (sandbox or not)
	 *
	 * @param string $path as path to add after default scheme://host/ infos.
	 *
	 * @return string as full URL
	 */
	public function get_alma_dashboard_url( $path = '' ) {
		if ( $this->settings->is_live() ) {
			/* translators: %s -> path to add after dashboard url */
			return esc_url( sprintf( __( 'https://dashboard.getalma.eu/%s', 'alma-gateway-for-woocommerce' ), $path ) );
		}

		/* translators: %s -> path to add after sandbox dashboard url */

		return esc_url( sprintf( __( 'https://dashboard.sandbox.getalma.eu/%s', 'alma-gateway-for-woocommerce' ), $path ) );
	}

	/**
	 * Check the share of checkout
	 * @return void
	 */
	public function check_share_checkout() {
		if ( ! is_admin() ) {
			return;
		}

		if ( 'yes' === alma_wc_plugin()->settings->share_of_checkout_enabled ) {
			return;
		}

		( new Alma_WC_Admin_Helper_Check_Legal() )->init();
	}

	/**
	 *  Check that Alma is available for the current currency
	 */
	public function check_currency() {
		$currency = get_woocommerce_currency();
		if ( 'EUR' !== $currency ) {
			$this->logger->info( "Currency $currency not supported - Not displaying Alma" );

			return false;
		}

		return true;
	}

	/**
	 * Register Alma payment gateway.
	 *
	 * @param string[] $gateways Payment gateways.
	 *
	 * @return string[]
	 */
	public function add_payment_gateway( $gateways ) {
		$gateways[] = 'Alma_WC_Payment_Gateway';

		return $gateways;
	}

	/** HELPERS **/

	/**
	 * AJAX handler for dismiss notice action.
	 */
	public function ajax_dismiss_notice() {
		if ( empty( $_POST['dismiss_action'] ) ) {
			return;
		}

		check_ajax_referer( 'alma_dismiss_notice', 'nonce' );
		if ( 'alma_dismiss_bootstrap_warning_message' === $_POST['dismiss_action'] ) {
			update_option( 'alma_bootstrap_warning_message_dismissed', true );
		}

		wp_die();
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

	/**
	 * Add relevant links to plugins page.
	 *
	 * @param array $links Plugin action links.
	 *
	 * @return array Plugin action links
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		if ( function_exists( 'WC' ) ) {
			$setting_url    = $this->get_admin_setting_url();
			$plugin_links[] = '<a href="' . esc_url( $setting_url ) . '">' . esc_html__( 'Settings', 'alma-gateway-for-woocommerce' ) . '</a>';
		}

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Handle customer return.
	 *
	 * @return void
	 */
	public function handle_customer_return() {
		$payment_id = $this->get_payment_to_validate();
		$gateway    = new Alma_WC_Payment_Gateway();
		$gateway->validate_payment_on_customer_return( $payment_id );
	}

	/**
	 * Webhooks handlers
	 *
	 * PID comes from Alma IPN callback or Alma Checkout page,
	 * it is not a user form submission: Nonce usage is not suitable here.
	 */
	private function get_payment_to_validate() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$id         = sanitize_text_field( $_GET['pid'] );
		$payment_id = isset( $id ) ? $id : null;

		if ( ! $payment_id ) {
			$this->logger->error( 'Payment validation webhook called without a payment ID' );

			wc_add_notice(
				__( 'Payment validation error: no ID provided.<br>Please try again or contact us if the problem persists.', 'alma-gateway-for-woocommerce' ),
				'error'
			);

			wp_safe_redirect( wc_get_cart_url() );
			exit();
		}

		return $payment_id;
	}

	/**
	 * Handle ipn callback.
	 *
	 * @return void
	 */
	public function handle_ipn_callback() {
		$payment_id = $this->get_payment_to_validate();
		$gateway    = new Alma_WC_Payment_Gateway();
		$gateway->validate_payment_from_ipn( $payment_id );
	}

	/**
	 * Retrieve fee_plans for current merchant
	 *
	 * @return FeePlan[]|array
	 *
	 * @throws RequestError On API exception.
	 */
	public function get_fee_plans() {
		$client = $this->get_alma_client();
		if ( ! $client ) {
			return array();
		}

		return $client->merchants->feePlans( FeePlan::KIND_GENERAL, 'all', true );
	}

	/**
	 * Get Eligibility / Payment formatted eligible plans definitions for current cart.
	 *
	 * @return array<array>
	 */
	public function get_eligible_plans_for_cart() {
		$amount = ( new Alma_WC_Model_Cart() )->get_total_in_cents();

		return array_values(
			array_map(
				function ( $plan ) use ( $amount ) {
					unset( $plan['max_amount'] );
					unset( $plan['min_amount'] );
					if ( isset( $plan['deferred_months'] ) && 0 === $plan['deferred_months'] ) {
						unset( $plan['deferred_months'] );
					}
					if ( isset( $plan['deferred_days'] ) && 0 === $plan['deferred_days'] ) {
						unset( $plan['deferred_days'] );
					}

					return $plan;
				},
				$this->settings->get_eligible_plans_definitions( $amount )
			)
		);
	}

	/**
	 * Check if cart has eligibilities.
	 *
	 * @return bool
	 */
	public function is_there_eligibility_in_cart() {
		return count( $this->get_eligible_plans_keys_for_cart() ) > 0;
	}

	/**
	 * Get eligible plans keys for current cart.
	 *
	 * @return array<string>
	 */
	public function get_eligible_plans_keys_for_cart() {
		$cart_eligibilities = $this->get_cart_eligibilities();

		return array_filter(
			$this->settings->get_eligible_plans_keys( ( new Alma_WC_Model_Cart() )->get_total_in_cents() ),
			function ( $key ) use ( $cart_eligibilities ) {
				return array_key_exists( $key, $cart_eligibilities );
			}
		);
	}

	/**
	 * Get eligibilities from cart.
	 *
	 * @return array<string,Eligibility>|null
	 */
	public function get_cart_eligibilities() {
		if ( ! $this->eligibilities ) {
			$alma = alma_wc_plugin()->get_alma_client();
			if ( ! $alma ) {
				return null;
			}

			try {
				$this->eligibilities = $alma->payments->eligibility( Alma_WC_Model_Payment::get_eligibility_payload_from_cart() );
			} catch ( RequestError $error ) {
				$this->logger->log_stack_trace( 'Error while checking payment eligibility: ', $error );

				return null;
			}
		}

		return $this->eligibilities;
	}

	/**
	 * Filter the alma gateway title (visible on checkout page).
	 *
	 * @param string $title The original title.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_title( $title, $id ) {

		if ( 'alma' !== substr( $id, 0, 4 ) ) {
			return $title;
		}

		$alma_settings = alma_wc_plugin()->settings;

		if ( 'alma' === $id ) {
			$title = $alma_settings->get_title( 'payment_method_pnx' );
		}

		if ( Alma_WC_Payment_Gateway::ALMA_GATEWAY_PAY_LATER === $id ) {
			$title = $alma_settings->get_title( 'payment_method_pay_later' );
		}

		if ( Alma_WC_Payment_Gateway::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			$title = $alma_settings->get_title( 'payment_method_pnx_plus_4' );
		}

		return $title;
	}

	/**
	 * Filter the alma gateway description (visible on checkout page).
	 *
	 * @param string $description The original description.
	 * @param integer $id The payment gateway id.
	 *
	 * @return string
	 */
	public function woocommerce_gateway_description( $description, $id ) {

		if ( 'alma' !== substr( $id, 0, 4 ) ) {
			return $description;
		}

		$alma_settings = alma_wc_plugin()->settings;

		if ( 'alma' === $id ) {
			$description = $alma_settings->get_description( 'payment_method_pnx' );
		}

		if ( Alma_WC_Payment_Gateway::ALMA_GATEWAY_PAY_LATER === $id ) {
			$description = $alma_settings->get_description( 'payment_method_pay_later' );
		}

		if ( Alma_WC_Payment_Gateway::ALMA_GATEWAY_PAY_MORE_THAN_FOUR === $id ) {
			$description = $alma_settings->get_description( 'payment_method_pnx_plus_4' );
		}

		return $description;
	}

	/**
	 * Inject JS in checkout page.
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		if ( is_checkout() ) {
			$alma_checkout = alma_wc_plugin()->get_asset_url( 'js/alma-checkout.js' );
			wp_enqueue_script( 'alma-checkout-page', $alma_checkout, array(), ALMA_WC_VERSION, true );
		}
	}

}
