<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Generic_Handler {
	private $logger;

	private $min_amount;
	private $max_amount;

	public function __construct() {
		$this->logger = new Alma_WC_Logger();

		if ( ! alma_wc_plugin()->settings->is_usable() ) {
			return;
		}

		$locale = get_locale();
		if ( $locale !== 'fr_FR' ) {
			$this->logger->info( "Locale {$locale} not supported - Not displaying Alma" );

			return;
		}

		$currency = get_woocommerce_currency();
		if ( $currency !== 'EUR' ) {
			$this->logger->info( "Currency {$currency} not supported - Not displaying Alma" );

			return;
		}

		if ( ! count( alma_wc_plugin()->settings->get_enabled_pnx_plans_list() ) ) {
			return;
		}

		$this->min_amount = alma_wc_get_min_eligible_amount_according_to_settings();
		$this->max_amount = alma_wc_get_max_eligible_amount_according_to_settings();
	}

	protected function inject_payment_plan_html_js(
		$eligibility_msg,
		$skip_payment_plan_injection, $amount = 0,
		$jquery_update_event = null,
		$amount_query_selector = null,
		$first_render = true
	) {
		$logo_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.svg' );

		$merchant_id   = alma_wc_plugin()->settings->merchant_id;
		$api_mode      = alma_wc_plugin()->settings->environment;
		$enabled_plans = alma_wc_plugin()->settings->get_enabled_pnx_plans_list();

		?>
		<div class="alma--eligibility-msg" style="margin: 15px 0">
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
			$alma_widjet_js_create_url = alma_wc_plugin()->get_asset_url( 'js/alma-widgets-create.js' );
			$alma_widjet_css_url       = alma_wc_plugin()->get_asset_url( 'css/alma-widgets.umd.css' );
			wp_enqueue_style( 'alma-widget', $alma_widjet_css_url, array(), false );
			wp_enqueue_script( 'alma-widget', $alma_widjet_js_url, array(), false, true );
			wp_enqueue_script( 'alma-widget-create', $alma_widjet_js_create_url, array(), false, true );
		}
	}
}
