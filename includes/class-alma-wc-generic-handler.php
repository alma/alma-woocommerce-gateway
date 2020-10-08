<?php
/**
 * Alma generic handler
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Generic_Handler
 */
class Alma_WC_Generic_Handler {
	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Min amount.
	 *
	 * @var float
	 */
	private $min_amount;

	/**
	 * Max amount.
	 *
	 * @var float
	 */
	private $max_amount;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();

		$this->min_amount = alma_wc_get_min_eligible_amount_according_to_settings();
		$this->max_amount = alma_wc_get_max_eligible_amount_according_to_settings();
	}


	/**
	 * Check whether we're in a situation where we can inject JS for our payment plan widget
	 *
	 * @return bool
	 */
	private function is_usable() {
		if ( ! alma_wc_plugin()->settings->is_usable() ) {
			return false;
		}

		$locale = get_locale();
		if ( 'fr_FR' !== $locale ) {
			$this->logger->info( "Locale {$locale} not supported - Not displaying Alma" );

			return false;
		}

		$currency = get_woocommerce_currency();
		if ( 'EUR' !== $currency ) {
			$this->logger->info( "Currency {$currency} not supported - Not displaying Alma" );

			return false;
		}

		if ( ! count( alma_wc_plugin()->settings->get_enabled_pnx_plans_list() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Inject payment plan.
	 *
	 * @param string      $eligibility_msg Eligibility msg.
	 * @param bool        $skip_payment_plan_injection Skip payment plan injection.
	 * @param int         $amount Amount.
	 * @param string|null $jquery_update_event Jquery update event.
	 * @param string|null $amount_query_selector Amount query selector.
	 * @param bool        $first_render First render.
	 *
	 * @return void
	 */
	protected function inject_payment_plan_html_js(
		$eligibility_msg,
		$skip_payment_plan_injection,
		$amount = 0,
		$jquery_update_event = null,
		$amount_query_selector = null,
		$first_render = true
	) {
		if ( ! $this->is_usable() ) {
			return;
		}

		$logo_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.svg' );

		$merchant_id = alma_wc_plugin()->settings->merchant_id;
		if ( empty( $merchant_id ) ) {
			return;
		}

		$enabled_plans = alma_wc_plugin()->settings->get_enabled_pnx_plans_list();
		$api_mode      = alma_wc_plugin()->settings->environment;

		?>
		<div class="alma--eligibility-msg" style="margin: 15px 0;">
			<img src="<?php echo esc_html( $logo_url ); ?>"
				style="width: auto !important; height: 25px !important; border: none !important; vertical-align: middle; display: inline-block;"
				alt="Alma"> <span style="text-transform: initial"><?php echo wp_kses_post( $eligibility_msg ); ?></span>
			<p>
				<div
					id="alma-payment-plan"
					data-merchant-id="<?php echo esc_attr( $merchant_id ); ?>"
					data-api-mode="<?php echo esc_attr( $api_mode ); ?>"
					data-amount="<?php echo esc_attr( $amount ); ?>"
					data-enabled-plans="<?php echo esc_attr( wp_json_encode( $enabled_plans ) ); ?>"
					data-min-amount="<?php echo esc_attr( $this->min_amount ); ?>"
					data-max-amount="<?php echo esc_attr( $this->max_amount ); ?>"
					data-amount-query-selector="<?php echo esc_attr( $amount_query_selector ); ?>"
					data-jquery-update-event="<?php echo esc_attr( $jquery_update_event ); ?>"
					data-first-render="<?php echo esc_attr( $first_render ); ?>"
				>
				</div>
			</p>
		</div>
		<?php
		if ( ! $skip_payment_plan_injection ) {
			$alma_widjet_js_url        = alma_wc_plugin()->get_asset_url( 'js/alma-widgets.umd.min.js' );
			$alma_widjet_js_create_url = alma_wc_plugin()->get_asset_url( 'js/alma-widgets-inject.js' );
			$alma_widjet_css_url       = alma_wc_plugin()->get_asset_url( 'css/alma-widgets.umd.css' );
			wp_enqueue_style( 'alma-widget', $alma_widjet_css_url, array(), ALMA_WC_VERSION );
			wp_enqueue_script( 'alma-widget', $alma_widjet_js_url, array(), ALMA_WC_VERSION, true );
			wp_enqueue_script( 'alma-widget-create', $alma_widjet_js_create_url, array(), ALMA_WC_VERSION, true );
		}
	}
}
