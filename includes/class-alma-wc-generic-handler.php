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

		$this->settings = alma_wc_plugin()->settings;

		$this->min_amount = $this->settings->get_min_eligible_amount();
		$this->max_amount = $this->settings->get_max_eligible_amount();
	}

	/**
	 * Check whether we're in a situation where we can inject JS for our payment plan widget
	 *
	 * @return bool
	 */
	private function is_usable() {
		if ( ! $this->settings->is_usable() ) {
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

		if ( ! count( $this->settings->get_enabled_pnx_plans() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Inject payment plan.
	 *
	 * @param bool        $skip_payment_plan_injection Skip payment plan injection.
	 * @param int         $amount Amount.
	 * @param string|null $jquery_update_event Jquery update event.
	 * @param string|null $amount_query_selector Amount query selector.
	 * @param bool        $first_render First render.
	 *
	 * @return void
	 */
	protected function inject_payment_plan_widget(
		$skip_payment_plan_injection,
		$amount = 0,
		$jquery_update_event = null,
		$amount_query_selector = null,
		$first_render = true
	) {
		if ( ! $this->is_usable() ) {
			return;
		}

		$merchant_id = $this->settings->merchant_id;
		if ( empty( $merchant_id ) ) {
			return;
		}

		$enabled_plans = $this->settings->get_enabled_pnx_plans();
		$api_mode      = $this->settings->environment;

		$widget_settings = array(
			'merchantId'          => $merchant_id,
			'apiMode'             => $api_mode,
			'amount'              => $amount,
			'enabledPlans'        => $enabled_plans,
			'amountQuerySelector' => $amount_query_selector,
			'jqueryUpdateEvent'   => $jquery_update_event,
			'firstRender'         => $first_render,
			'decimalSeparator'    => wc_get_price_decimal_separator(),
			'thousandSeparator'   => wc_get_price_thousand_separator(),
		)
		?>
		<div style="margin: 15px 0; max-width: 350px">
			<div id="alma-payment-plans" data-settings="<?php echo esc_attr( wp_json_encode( $widget_settings ) ); ?>"></div>
		</div>
		<?php
		if ( ! $skip_payment_plan_injection ) {
			$alma_widgets_js_url        = 'https://unpkg.io/@alma/widgets@1.x.x/dist/alma-widgets.umd.js';
			$alma_widgets_js_create_url = alma_wc_plugin()->get_asset_url( 'js/alma-widgets-inject.js' );
			$alma_widgets_css_url       = 'https://unpkg.io/@alma/widgets@1.x.x/dist/alma-widgets.css';
			wp_enqueue_style( 'alma-widgets', $alma_widgets_css_url, array(), null );
			wp_enqueue_script( 'alma-widgets', $alma_widgets_js_url, array(), null, true );
			wp_enqueue_script( 'alma-widgets-create', $alma_widgets_js_create_url, array(), ALMA_WC_VERSION, true );
		}
	}

	/**
	 * Is product excluded.
	 *
	 * @param int $product_id Product Id.
	 *
	 * @return bool
	 */
	protected function is_product_excluded( $product_id ) {
		foreach ( alma_wc_plugin()->settings->excluded_products_list as $category_slug ) {
			if ( has_term( $category_slug, 'product_cat', $product_id ) ) {
				return true;
			}
		}

		return false;
	}
}
