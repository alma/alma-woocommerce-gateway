<?php
/**
 * GenericHandler.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Handlers
 * @namespace Alma\Woocommerce\Handlers
 */

namespace Alma\Woocommerce\Handlers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Factories\CoreFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;

/**
 * GenericHandler
 */
class GenericHandler {


	/**
	 * Has any child handler already rendered the widget in this page
	 *
	 * @var bool
	 */
	private static $is_already_rendered = false;
	/**
	 * Logger
	 *
	 * @var AlmaLogger
	 */
	protected $logger;
	/**
	 * Plugin settings
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;
	/**
	 * The tool helper.
	 *
	 * @var ToolsHelper
	 */
	protected $helper_tools;


	/**
	 * The core factory.
	 *
	 * @var CoreFactory
	 */
	protected $core_factory;
	/**
	 * The price factory.
	 *
	 * @var PriceFactory
	 */
	protected $price_factory;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->logger         = new AlmaLogger();
		$this->alma_settings  = new AlmaSettings();
		$tools_helper_builder = new ToolsHelperBuilder();
		$this->helper_tools   = $tools_helper_builder->get_instance();
		$this->price_factory  = new PriceFactory();
		$this->core_factory   = new CoreFactory();
	}

	/**
	 * A translated message about already rendered widget.
	 *
	 * @return string|null
	 */
	public function get_eligibility_widget_already_rendered_message() {
		return __( 'Alma "Eligibility Widget" (cart or product) already rendered on this page - Not displaying Alma', 'alma-gateway-for-woocommerce' );
	}

	/**
	 * Inject payment plan.
	 *
	 * @param bool        $has_excluded_products Skip payment plan injection if true.
	 * @param int         $amount Amount.
	 * @param string|null $jquery_update_event Jquery update event.
	 * @param string|null $amount_query_selector Amount query selector.
	 * @param string|null $amount_sale_price_query_selector Amount query selector for products with sale variation price.
	 *
	 * @return void
	 */
	protected function inject_payment_plan_widget(
		$has_excluded_products,
		$amount = 0,
		$jquery_update_event = null,
		$amount_query_selector = null,
		$amount_sale_price_query_selector = null
	) {
		if ( $this->is_already_rendered() ) {
			$this->logger->info( 'Alma "Eligibility Widget" (cart or product) is already rendered on this page - Not displaying Alma.' );
			return;
		}

		if ( ! $this->is_usable() ) {
			$this->logger->info( 'Handler is not usable: badge injection failed.' );
			return;
		}

		$merchant_id = $this->alma_settings->get_active_merchant_id();
		if ( empty( $merchant_id ) ) {
			$this->logger->info( 'AlmaSettings merchant id not found: badge injection failed.' );
			return;
		}

		$widget_settings = array(
			'hasExcludedProducts'          => $has_excluded_products,
			'merchantId'                   => $merchant_id,
			'apiMode'                      => $this->alma_settings->get_environment(),
			'amount'                       => $amount,
			'enabledPlans'                 => $this->filter_plans_definitions( $this->alma_settings->get_enabled_plans_definitions() ),
			'amountQuerySelector'          => $amount_query_selector,
			'amountSalePriceQuerySelector' => $amount_sale_price_query_selector,
			'jqueryUpdateEvent'            => $jquery_update_event,
			'firstRender'                  => true,
			'decimalSeparator'             => $this->price_factory->get_woo_decimal_separator(),
			'thousandSeparator'            => $this->price_factory->get_woo_thousand_separator(),
			'locale'                       => substr( get_locale(), 0, 2 ),
		);

		// Inject JS/CSS required for the eligibility/payment plans info display.
		$alma_widgets_js_url = AssetsHelper::get_asset_url( 'widget/js/widgets-wc.umd.js' );
		wp_enqueue_script( 'alma-widgets', $alma_widgets_js_url, array(), ALMA_VERSION, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

		$alma_widgets_css_url = AssetsHelper::get_asset_url( 'widget/css/widgets.css' );
		wp_enqueue_style( 'alma-widgets', $alma_widgets_css_url, array(), ALMA_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

		$alma_widgets_injection_url = AssetsHelper::get_asset_url( 'js/alma-widgets-inject.js' );
		wp_enqueue_script( 'alma-widgets-injection', $alma_widgets_injection_url, array(), ALMA_VERSION, true );

		?>
		<style>#alma-payment-plans button {
				background-color: white;
			}</style>
		<div style="margin: 15px 0; max-width: 350px">
			<div id="alma-payment-plans" data-settings="<?php echo esc_attr( wp_json_encode( $widget_settings ) ); ?>">
			</div>

			<?php
			if ( $has_excluded_products ) {
				$logo_url      = AssetsHelper::get_asset_url( ConstantsHelper::ALMA_LOGO_PATH );
				$exclusion_msg = $this->get_cart_not_eligible_message_gift_cards();
				?>
				<img src="<?php echo esc_attr( $logo_url ); ?>" alt="Alma" style="width: auto !important; height: 25px !important; border: none !important; vertical-align: middle; display: inline-block;">
				<span style="text-transform: initial"><?php echo wp_kses_post( $exclusion_msg ); ?></span>
				<?php
			}
			?>
		</div>
		<?php
		self::$is_already_rendered = true;
	}

	/**
	 * Has any child handler already rendered the widget in this page
	 *
	 * @return bool as rendered widget state.
	 */
	public function is_already_rendered() {
		return self::$is_already_rendered;
	}

	/**
	 * Check whether we're in a situation where we can inject JS for our payment plan widget
	 *
	 * @return bool
	 */
	protected function is_usable() {
		if ( ! $this->alma_settings->is_enabled() ) {
			$this->logger->info( 'Not usable handler: settings are disabled.' );

			return false;
		}

		if ( ! $this->alma_settings->has_keys() ) {
			$this->logger->info( 'Not usable handler: settings are not fully configured.' );

			return false;
		}

		if ( ! $this->helper_tools->check_currency() ) {
			return false;
		}

		if ( ! count( $this->alma_settings->get_enabled_plans_definitions() ) ) {
			$this->logger->info( 'No payment plans have been activated - Not displaying Alma.' );
			return false;
		}

		return true;
	}

	/**
	 * Filter & format enabled plans to match data-settings.enabledPlans allowed value.
	 *
	 * @param array $plans_settings Plans definitions to filter & format.
	 *
	 * @return array
	 */
	protected function filter_plans_definitions( $plans_settings ) {
		return array_values( // Remove plan_keys from enabled plans definitions.
			array_filter(
				$plans_settings,
				function ( $plan_definition ) {
					if ( ! isset( $plan_definition['installments_count'] ) ) { // Widget does not work fine without installments_count.
						return false;
					}

					return true;
				}
			)
		);
	}

	/**
	 * Gets not eligible cart message.
	 *
	 * @return string
	 */
	public function get_cart_not_eligible_message_gift_cards() {
		return $this->alma_settings->get_i18n( 'cart_not_eligible_message_gift_cards' );
	}

	/**
	 * Check if a given product is excluded.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	protected function is_product_excluded( $product_id ) {
		foreach ( $this->alma_settings->excluded_products_list as $category_slug ) {
			if ( $this->core_factory->has_term( $category_slug, 'product_cat', $product_id ) ) {
				return true;
			}
		}

		return false;
	}
}
