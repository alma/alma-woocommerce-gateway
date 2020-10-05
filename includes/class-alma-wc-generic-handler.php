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

	protected $jquery_update_event;

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

	protected function inject_payment_plan_html_js( $eligibility_msg, $skip_payment_plan_injection, $amount ) {
		$logo_url = alma_wc_plugin()->get_asset_url( 'images/alma_logo.png' );

		$enabled_plans = alma_wc_plugin()->settings->get_enabled_pnx_plans_list();

		?>
		<div class="alma--eligibility-msg" style="margin: 15px 0">
			<img src="<?php echo esc_html( $logo_url ); ?>"
				style="width: auto !important; height: 25px !important; border: none !important; vertical-align: middle; display: inline-block;"
				alt="Alma"> <span style="text-transform: initial"><?php echo wp_kses_post( $eligibility_msg ); ?></span>
			<p>
				<div
					id="alma-payment-plan"
					data-amount="<?php echo esc_attr( $amount ); ?>"
					data-enabled-plans="<?php echo esc_attr( wp_json_encode( $enabled_plans ) ); ?>"
				>
				</div>
			</p>
		</div>
		<?php
		if ( ! $skip_payment_plan_injection ) {
			$alma_widjet_js_url  = alma_wc_plugin()->get_asset_url( 'js/alma-widgets.umd.min.js' );
			$alma_widjet_css_url = alma_wc_plugin()->get_asset_url( 'css/alma-widgets.umd.css' );
			$alma_widget_handle  = 'alma-widget';
			wp_enqueue_style( $alma_widget_handle, $alma_widjet_css_url, array(), false );
			wp_enqueue_script( $alma_widget_handle, $alma_widjet_js_url, array(), false, true );
			wp_add_inline_script( $alma_widget_handle, $this->get_inline_script( $amount ) );
		}
	}

	private function get_inline_script() {
		$merchant_id = alma_wc_plugin()->settings->merchant_id;
		$api_mode    = alma_wc_plugin()->settings->environment;

		$inline_script = '
		window.AlmaInitWidget = function () {
			var amount = parseInt(jQuery("#alma-payment-plan").data("amount"));
			var enabledPlans = jQuery("#alma-payment-plan").data("enabled-plans");

			var eligibleInstallments = enabledPlans.filter(function(plan) {
				return amount >= plan.min_amount && amount <= plan.max_amount;
			}).map(function (plan) { return plan.installments; });

			var almaWidgets = Alma.Widgets.initialize("' . esc_js( $merchant_id ) . '", "' . esc_js( $api_mode ) . '");

			almaWidgets.create(Alma.Widgets.PaymentPlan, {
				container: "#alma-payment-plan",
				purchaseAmount: amount,
				installmentsCount: eligibleInstallments,
				minPurchaseAmount: ' . esc_js( $this->min_amount ) . ',
				maxPurchaseAmount: ' . esc_js( $this->max_amount ) . ',
				templates: {
					notEligible: function(min, max, installmentsCounts, config, createWidget) {
						return "<b>Le paiement en plusieurs fois est disponible entre €" + min / 100 + " et €" + max / 100 + "</b>";
					}
				}
			});

			almaWidgets.render();
		};

		window.AlmaInitWidget();
		';

		if ( $this->jquery_update_event ) {
			$inline_script .= '
				jQuery( document.body ).on( "' . esc_js( $this->jquery_update_event ) . '", function() {
					window.AlmaInitWidget();
				});
			';
		}

		return $inline_script;
	}
}
