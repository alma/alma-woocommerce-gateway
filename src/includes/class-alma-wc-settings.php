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
 * @property string payment_upon_trigger_enabled Bool for triggering payments
 * @property string payment_upon_trigger_event WC event to trigger payment
 * @property string payment_upon_trigger_display_text Key of text to display to front-end user for payment on trigger
 * @property string live_api_key Live api key
 * @property string test_api_key Test api key
 * @property string enabled Wp-bool-eq (yes or no)
 * @property string debug Wp-bool-eq (yes or no)
 * @property string display_product_eligibility Wp-bool-eq (yes or no)
 * @property string display_cart_eligibility Wp-bool-eq (yes or no)
 * @property string environment Live or test
 * @property bool fully_configured Flag to indicate setting are fully configured by the plugin
 * @property string selected_fee_plan Admin dashboard fee_plan in edition mode.
 * @property string merchant_id Alma merchant ID
 * @property string variable_product_price_query_selector Css query selector
 * @property string variable_product_sale_price_query_selector Css query selector for variable discounted products
 * @property string variable_product_check_variations_event JS event for product variation change
 * @property array excluded_products_list Wp Categories excluded slug's list
 * @property string share_of_checkout_enabled Bool for share of checkout acceptance (yes or no)
 * @property string share_of_checkout_enabled_date String Date when the marchand did accept the share of checkout
 */
class Alma_WC_Settings {
	const OPTIONS_KEY = 'woocommerce_alma_settings'; // Generated by WooCommerce in WC_Settings_API::get_option_key().
	const DEFAULT_FEE_PLAN = 'general_3_0_0';
	const DEFAULT_CHECK_VARIATIONS_EVENT = 'check_variations';

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
	 * __construct.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load();
	}

	/**
	 * Loads settings from DB.
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
	 * Get default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'enabled'                                    => 'yes',
			'payment_upon_trigger_enabled'               => 'no',
			'payment_upon_trigger_event'                 => 'completed',
			'payment_upon_trigger_display_text'          => 'at_shipping',
			'selected_fee_plan'                          => self::DEFAULT_FEE_PLAN,
			'enabled_general_3_0_0'                      => 'yes',
			'title_payment_method_pnx'                   => Alma_WC_Settings_Helper::default_pnx_title(),
			'description_payment_method_pnx'             => Alma_WC_Settings_Helper::default_payment_description(),
			'title_payment_method_pay_later'             => Alma_WC_Settings_Helper::default_pay_later_title(),
			'description_payment_method_pay_later'       => Alma_WC_Settings_Helper::default_payment_description(),
			'title_payment_method_pnx_plus_4'            => Alma_WC_Settings_Helper::default_pnx_plus_4_title(),
			'description_payment_method_pnx_plus_4'      => Alma_WC_Settings_Helper::default_payment_description(),
			'display_cart_eligibility'                   => 'yes',
			'display_product_eligibility'                => 'yes',
			'variable_product_price_query_selector'      => Alma_WC_Settings_Helper::default_variable_price_selector(),
			'variable_product_sale_price_query_selector' => Alma_WC_Settings_Helper::default_variable_sale_price_selector(),
			'variable_product_check_variations_event'    => self::DEFAULT_CHECK_VARIATIONS_EVENT,
			'excluded_products_list'                     => array(),
			'cart_not_eligible_message_gift_cards'       => Alma_WC_Settings_Helper::default_not_eligible_cart_message(),
			'live_api_key'                               => '',
			'test_api_key'                               => '',
			'environment'                                => 'test',
			'share_of_checkout_enabled'                  => 'no',
			'debug'                                      => 'yes',
			'fully_configured'                           => false,
		);
	}

	/**
	 * __isset.
	 *
	 * @param string $key Key.
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return array_key_exists( $key, $this->settings );
	}

	/**
	 * Updates from settings.
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
	 * Saves settings.
	 *
	 * @return void
	 */
	public function save() {
		update_option( self::OPTIONS_KEY, $this->settings );
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
	 * Gets title for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 *
	 * @return string
	 */
	public function get_title( $payment_method ) {
		return $this->get_i18n( 'title_' . $payment_method );
	}

	/**
	 * Gets a setting value translated.
	 *
	 * @param string $key The setting to translate.
	 *
	 * @return string
	 */
	private function get_i18n( $key ) {

		if ( Alma_WC_Internationalization::is_site_multilingual() ) {
			if ( $this->{$key . '_' . get_locale()} ) {
				return $this->{$key . '_' . get_locale()};
			}

			return Alma_WC_Internationalization::get_translated_text(
				self::default_settings()[ $key ],
				get_locale()
			);
		}

		return $this->{$key};
	}

	/**
	 * Gets description for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 *
	 * @return string
	 */
	public function get_description( $payment_method ) {
		return $this->get_i18n( 'description_' . $payment_method );
	}

	/**
	 * Gets eligible plans definitions for amount.
	 *
	 * @param int $amount The amount to pay.
	 *
	 * @return array<array> As eligible plans definitions.
	 */
	public function get_eligible_plans_definitions( $amount ) {
		return array_filter(
			$this->get_enabled_plans_definitions(),
			function ( $plan ) use ( $amount ) {
				return $this->is_eligible( $plan, $amount );
			}
		);
	}

	/**
	 * Gets enabled plans and configuration summary stored in settings for each enabled plan.
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
	 * Gets allowed fee plans keys.
	 *
	 * @return array|string[]
	 * @see get_allowed_fee_plans
	 */
	public function get_allowed_plans_keys() {
		return array_map(
			function ( FeePlan $fee_plan ) {
				return $fee_plan->getPlanKey();
			},
			$this->get_allowed_fee_plans()
		);
	}

	/**
	 * Retrieves allowed fee plans definition from the merchant.
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
			function ( $fee_plan ) {
				return $this->is_allowed_fee_plan( $fee_plan );
			}
		);

		return $this->allowed_fee_plans;
	}

	/**
	 * Does need API key ?
	 *
	 * @return bool
	 */
	public function need_api_key() {
		return empty( $this->get_active_api_key() );
	}

	/**
	 * Gets API string for the current environment.
	 *
	 * @return string
	 */
	public function get_active_api_key() {
		return $this->is_live() ? $this->get_live_api_key() : $this->get_test_api_key();
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
	 * Gets active environment from setting.
	 *
	 * @return string
	 */
	public function get_environment() {
		return 'live' === $this->environment ? 'live' : 'test';
	}

	/**
	 * Gets API key for live environment.
	 *
	 * @return string
	 */
	protected function get_live_api_key() {
		return $this->live_api_key;
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string
	 */
	protected function get_test_api_key() {
		return $this->test_api_key;
	}

	/**
	 * Says if a fee_plan is allowed or not based on Alma fee plans settings & business rules.
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
	 * __get.
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
	 * __set.
	 *
	 * @param string $key Key.
	 * @param mixed $value Value.
	 *
	 * @return void
	 */
	public function __set( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Gets installments_count for plan.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_installments_count( $key ) {
		return $this->__get( "installments_count_$key" );
	}

	/**
	 * Gets min amount for pnx.
	 *
	 * @param string $key The plan key.
	 *
	 * @return int
	 */
	public function get_min_amount( $key ) {
		return $this->__get( "min_amount_$key" );
	}

	/**
	 * Gets max amount for pnx.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_max_amount( $key ) {
		return $this->__get( "max_amount_$key" );
	}

	/**
	 * Gets deferred days for plan.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_deferred_days( $key ) {
		return $this->__get( "deferred_days_$key" );
	}

	/**
	 * Gets deferred months for plan.
	 *
	 * @param int $key The plan key.
	 *
	 * @return int
	 */
	public function get_deferred_months( $key ) {
		return $this->__get( "deferred_months_$key" );
	}

	/**
	 * Checks if a plan is eligible.
	 *
	 * @param array $plan Plan definition.
	 * @param int $amount Price.
	 *
	 * @return bool
	 */
	protected function is_eligible( $plan, $amount ) {
		return $amount >= $plan['min_amount'] && $amount <= $plan['max_amount'];
	}

	/**
	 * Gets eligible plans keys for amount.
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
	 * Is using test API.
	 *
	 * @return bool
	 */
	public function is_test() {
		return $this->get_environment() === 'test';
	}

	/**
	 * Tells if the merchant has at least one "pay later" payment method enabled in the WC back-office.
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
	 * Tells if the merchant has at least one "pnx_plus_4" payment method enabled in the WC back-office.
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

	/**
	 * Gets not eligible cart message.
	 *
	 * @return string
	 */
	public function get_cart_not_eligible_message_gift_cards() {
		return $this->get_i18n( 'cart_not_eligible_message_gift_cards' );
	}


}
