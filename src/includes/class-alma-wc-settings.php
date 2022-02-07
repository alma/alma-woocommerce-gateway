<?php
/**
 * Alma settings
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\Entities\FeePlan;
use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 *
 * @property string live_api_key Live api key
 * @property string test_api_key Test api key
 * @property string enabled Wp-bool-eq (yes or no)
 * @property string debug Wp-bool-eq (yes or no)
 * @property string display_product_eligibility Wp-bool-eq (yes or no)
 * @property string display_cart_eligibility Wp-bool-eq (yes or no)
 * @property string environment Live or test
 * @property bool   fully_configured Flag to indicate setting are fully configured by the plugin.
 * @property string selected_fee_plan Admin dashboard fee_plan in edition mode.
 * @property string merchant_id Alma merchant ID
 * @property string variable_product_price_query_selector Css query selector
 * @property string cart_not_eligible_message_gift_cards Message to display
 * @property array  excluded_products_list Wp Categories excluded slug's list
 */
class Alma_WC_Settings {
	const OPTIONS_KEY      = 'woocommerce_alma_settings'; // Generated by WooCommerce in WC_Settings_API::get_option_key().
	const DEFAULT_FEE_PLAN = 'general_3_0_0';

	/**
	 * Setting values from get_option.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Flag to indicate setting has been loaded from DB.
	 *
	 * @var bool
	 */
	private $are_settings_loaded = false;

	/**
	 * Merchant available plans.
	 *
	 * @var array<FeePlan>
	 */
	private $allowed_fee_plans;

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		$payment_methods_description = __( 'Fast and secure payment by credit card', 'alma-woocommerce-gateway' );
		return array(
			'enabled'                               => 'yes',
			'selected_fee_plan'                     => self::DEFAULT_FEE_PLAN,
			'enabled_general_3_0_0'                 => 'yes',
			'title_payment_method_pnx'              => __( 'Pay in installments with Alma', 'alma-woocommerce-gateway' ),
			'description_payment_method_pnx'        => $payment_methods_description,
			'title_payment_method_pay_later'        => __( 'Buy now, Pay later with Alma', 'alma-woocommerce-gateway' ),
			'description_payment_method_pay_later'  => $payment_methods_description,
			'title_payment_method_pnx_plus_4'       => __( 'Spread your payments with Alma', 'alma-woocommerce-gateway' ),
			'description_payment_method_pnx_plus_4' => $payment_methods_description,
			'display_cart_eligibility'              => 'yes',
			'display_product_eligibility'           => 'yes',
			'variable_product_price_query_selector' => Alma_WC_Product_Handler::default_variable_price_selector(),
			'excluded_products_list'                => array(),
			'cart_not_eligible_message_gift_cards'  => __( 'Some products cannot be paid with monthly or deferred installments', 'alma-woocommerce-gateway' ),
			'live_api_key'                          => '',
			'test_api_key'                          => '',
			'environment'                           => 'test',
			'debug'                                 => 'yes',
			'fully_configured'                      => false,
		);
	}

	/**
	 * __get
	 *
	 * @param string $key Key.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		$value = null;

		if ( array_key_exists( $key, $this->settings ) ) {
			$value = $this->settings[ $key ];
		}

		return apply_filters( 'alma_wc_settings_' . $key, $value );
	}

	/**
	 * __set
	 *
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 *
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * __isset
	 *
	 * @param string $key Key.
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return array_key_exists( $key, $this->settings );
	}

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load();
	}

	/**
	 * Load settings from DB.
	 */
	protected function load() {
		if ( $this->are_settings_loaded ) {
			return;
		}
		$settings                  = (array) get_option( self::OPTIONS_KEY, array() );
		$this->settings            = array_merge( self::default_settings(), $settings );
		$this->are_settings_loaded = true;
	}

	/**
	 * Update from settings.
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return void
	 */
	public function update_from( $settings = array() ) {
		foreach ( $settings as $key => $value ) {
			$this->settings[ $key ] = $value;
		}
		$this->save();
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function save() {
		update_option( self::OPTIONS_KEY, $this->settings );
	}

	/**
	 * Get API key for live environment.
	 *
	 * @return string
	 */
	protected function get_live_api_key() {
		return $this->live_api_key;
	}

	/**
	 * Get API key for test environment.
	 *
	 * @return string
	 */
	protected function get_test_api_key() {
		return $this->test_api_key;
	}

	/**
	 * Get API string for the current environment.
	 *
	 * @return string
	 */
	public function get_active_api_key() {
		return $this->is_live() ? $this->get_live_api_key() : $this->get_test_api_key();
	}

	/**
	 * Need API key.
	 *
	 * @return bool
	 */
	public function need_api_key() {
		return empty( $this->get_active_api_key() );
	}

	/**
	 * Is plugin enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Is plan enabled.
	 *
	 * @param int $key plan key.
	 *
	 * @return bool
	 */
	private function is_plan_enabled( $key ) {
		return 'yes' === $this->__get( "enabled_$key" );
	}

	/**
	 * Get enabled plans and configuration summary stored in settings for each enabled plan.
	 *
	 * @return array<array> containing arrays with plans configurations.
	 */
	public function get_enabled_plans_definitions() {
		$plans = array();

		foreach ( $this->get_allowed_plans_keys() as $key ) {
			if ( $this->is_plan_enabled( $key ) ) {
				$plans[ $key ] = array(
					'installments_count' => $this->get_installments_count( $key ),
					'min_amount'         => $this->get_min_amount( $key ),
					'max_amount'         => $this->get_max_amount( $key ),
					'deferred_days'      => $this->get_deferred_days( $key ),
					'deferred_months'    => $this->get_deferred_months( $key ),
				);
			}
		}

		return $plans;
	}

	/**
	 * Get title for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 *
	 * @return string
	 */
	public function get_title( $payment_method ) {
		$title = $this->__get( "title_$payment_method" );
		if ( ! $title ) {
			$title = self::default_settings()[ "title_$payment_method" ];
		}
		return $title;
	}

	/**
	 * Get description for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 *
	 * @return string
	 */
	public function get_description( $payment_method ) {
		$description = $this->__get( "description_$payment_method" );
		if ( ! $description ) {
			$description = self::default_settings()[ "description_$payment_method" ];
		}
		return $description;
	}

	/**
	 * Get min amount for pnx.
	 *
	 * @param string $key the plan key.
	 *
	 * @return int
	 */
	public function get_min_amount( $key ) {
		return $this->__get( "min_amount_$key" );
	}

	/**
	 * Get max amount for pnx.
	 *
	 * @param int $key the plan key.
	 *
	 * @return int
	 */
	public function get_max_amount( $key ) {
		return $this->__get( "max_amount_$key" );
	}

	/**
	 * Get eligible plans definitions for amount.
	 *
	 * @param int $amount the amount to pay.
	 *
	 * @return array<array> As eligible plans definitions.
	 */
	public function get_eligible_plans_definitions( $amount ) {
		return array_filter(
			$this->get_enabled_plans_definitions(),
			function( $plan ) use ( $amount ) {
				return $this->is_eligible( $plan, $amount );
			}
		);
	}

	/**
	 * Get eligible plans keys for amount.
	 *
	 * @param int $amount the amount to pay.
	 *
	 * @return array<string> as eligible plans keys
	 */
	public function get_eligible_plans_keys( $amount ) {
		$eligible_keys = array();
		foreach ( $this->get_enabled_plans_definitions() as $key => $plan ) {
			if ( $this->is_eligible( $plan, $amount ) ) {
				$eligible_keys[] = $key;
			}
		}
		return $eligible_keys;
	}

	/**
	 * Is logging enabled.
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return 'yes' === $this->debug;
	}

	/**
	 * Get active environment from setting.
	 *
	 * @return string
	 */
	public function get_environment() {
		return 'live' === $this->environment ? 'live' : 'test';
	}

	/**
	 * Is using live API.
	 *
	 * @return bool
	 */
	public function is_live() {
		return $this->get_environment() === 'live';
	}

	/**
	 * Is using test API.
	 *
	 * @return bool
	 */
	public function is_test() {
		return $this->get_environment() === 'test';
	}

	/**
	 * Retrieve allowed fee plans definition from the merchant
	 *
	 * @return array<FeePlan>
	 * @see is_allowed_fee_plan()
	 */
	public function get_allowed_fee_plans() {
		if ( $this->need_api_key() ) {
			return array();
		}
		if ( $this->allowed_fee_plans ) {
			return $this->allowed_fee_plans;
		}
		$this->allowed_fee_plans = array();
		$fee_plans               = null;
		try {
			$fee_plans = alma_wc_plugin()->get_fee_plans();
		} catch ( RequestError $e ) {
			alma_wc_plugin()->handle_settings_exception( $e );
		}
		if ( ! $fee_plans ) {
			return array();
		}
		$this->allowed_fee_plans = array_filter(
			$fee_plans,
			function( $fee_plan ) {
				return $this->is_allowed_fee_plan( $fee_plan );
			}
		);

		return $this->allowed_fee_plans;
	}

	/**
	 * Get allowed fee plans keys
	 *
	 * @return array|string[]
	 * @see get_allowed_fee_plans
	 */
	public function get_allowed_plans_keys() {
		return array_map(
			function( FeePlan $fee_plan ) {
				return $fee_plan->getPlanKey();
			},
			$this->get_allowed_fee_plans()
		);
	}

	/**
	 * Say if a fee_plan is allowed or not based on Alma fee plans settings & business rules.
	 *
	 * @param FeePlan $fee_plan as fee_plan to evaluate.
	 *
	 * @return bool
	 */
	private function is_allowed_fee_plan( FeePlan $fee_plan ) {
		if ( ! $fee_plan->allowed ) {
			return false;
		}
		if ( $fee_plan->isPayLaterOnly() || $fee_plan->isPnXOnly() ) {
			return true;
		}

		return false;
	}

	/**
	 * Get deferred days for plan.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_deferred_days( $key ) {
		return $this->__get( "deferred_days_$key" );
	}

	/**
	 * Get deferred months for plan.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_deferred_months( $key ) {
		return $this->__get( "deferred_months_$key" );
	}

	/**
	 * Get installments_count for plan.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_installments_count( $key ) {
		return $this->__get( "installments_count_$key" );
	}

	/**
	 * Check if a plan is eligible.
	 *
	 * @param array $plan Plan definition.
	 * @param int   $amount Price.
	 *
	 * @return bool
	 */
	protected function is_eligible( $plan, $amount ) {
		return $amount >= $plan['min_amount'] && $amount <= $plan['max_amount'];
	}

	/**
	 * Tells if the marchand has at least one "pay later" payment method enabled in the WC back-office.
	 *
	 * @return bool
	 */
	public function has_pay_later() {
		foreach ( $this->get_enabled_plans_definitions() as $plan_definition ) {
			if ( $plan_definition['deferred_days'] >= 1 || $plan_definition['deferred_months'] >= 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells if the marchand has at least one "pnx_plus_4" payment method enabled in the WC back-office.
	 *
	 * @return bool
	 */
	public function has_pnx_plus_4() {
		foreach ( $this->get_enabled_plans_definitions() as $plan_definition ) {
			if ( $plan_definition['installments_count'] > 4 ) {
				return true;
			}
		}
		return false;
	}


}
