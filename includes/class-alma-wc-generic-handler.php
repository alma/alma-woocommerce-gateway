<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Generic_Handler {
	private $logger;

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

		if ( ! count( alma_wc_plugin()->settings->get_enabled_pnx_list() ) ) {
			return;
		}
	}

	protected function inject_payment_plan_html_js( $eligibility_msg, $skip_payment_plan_injection, $amount ) {
		$logo_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.png' );

		$min_amount = alma_wc_get_min_eligible_amount_according_to_settings();
		$max_amount = alma_wc_get_max_eligible_amount_according_to_settings();

		if ( $amount > $max_amount ) {
			$eligibility_msg .= '<br>' . sprintf( __( '(Maximum amount: %s)', 'alma-woocommerce-gateway' ), wc_price( alma_wc_price_from_cents( $max_amount ), array( 'decimals' => 0 ) ) );
		} elseif ( $amount < $min_amount ) {
			$eligibility_msg .= '<br>' . sprintf( __( '(Minimum amount: %s)', 'alma-woocommerce-gateway' ), wc_price( alma_wc_price_from_cents( $min_amount ), array( 'decimals' => 0 ) ) );
		}

		?>
		<div class="alma--eligibility-msg" style="margin: 15px 0">
			<img src="<?php echo esc_html( $logo_url ); ?>"
				style="width: auto !important; height: 25px !important; border: none !important; vertical-align: middle; display: inline-block;"
				alt="Alma"> <span style="text-transform: initial"><?php echo wp_kses_post( $eligibility_msg ); ?></span>
			<p><div id="payment-plan"></div></p>
		</div>
		<?php
		if ( ! $skip_payment_plan_injection ) {
			$merchant_id           = alma_wc_plugin()->settings->merchant_id;
			$api_mode              = alma_wc_plugin()->settings->environment;
			$eligible_installments = alma_wc_get_eligible_installments_according_to_settings( $amount );

			$alma_widjet_js_url  = alma_wc_plugin()->get_asset_url( 'js/alma-widgets.umd.min.js' );
			$alma_widjet_css_url = alma_wc_plugin()->get_asset_url( 'css/alma-widgets.umd.css' );
			$alma_widget_handle  = 'alma-widget';
			wp_enqueue_style( $alma_widget_handle, $alma_widjet_css_url, array(), false );
			wp_enqueue_script( $alma_widget_handle, $alma_widjet_js_url, array(), false, true );
			wp_add_inline_script(
				$alma_widget_handle,
				'(function () {
					var almaWidgets = Alma.Widgets.initialize("' . esc_js( $merchant_id ) . '", "' . esc_js( $api_mode ) . '");
					almaWidgets.create(Alma.Widgets.PaymentPlan, {
						container: "#payment-plan",
						purchaseAmount: ' . esc_js( $amount ) . ',
						installmentsCount: ' . wp_json_encode( $eligible_installments ) . ',
					});
					almaWidgets.render();
				})();'
			);
		}
	}
}
