<?php
/**
 * Alma_Settings.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes
 * @namespace Alma\Woocommerce
 */

namespace Alma\Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\API\Client;
use Alma\API\DependenciesError;
use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Payment;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\Exceptions\Alma_Api_Share_Of_Checkout_Accept;
use Alma\Woocommerce\Exceptions\Alma_Api_Share_Of_Checkout_Deny;
use Alma\Woocommerce\Exceptions\Alma_Api_Soc_Last_Update_Dates;
use Alma\Woocommerce\Helpers\Alma_Settings as Alma_Helper_Settings;
use Alma\Woocommerce\Exceptions\Alma_Plans_Definition;
use Alma\Woocommerce\Helpers\Alma_General;
use Alma\Woocommerce\Models\Alma_Cart;
use Alma\Woocommerce\Helpers\Alma_Constants;
use Alma\Woocommerce\Models\Alma_Payment;
use Alma\Woocommerce\Helpers\Alma_Internationalization;
use Alma\Woocommerce\Exceptions\Alma_Api_Create_Payments;
use Alma\Woocommerce\Exceptions\Alma_Exception;
use Alma\Woocommerce\Exceptions\Alma_Api_Fetch_Payments;
use Alma\Woocommerce\Exceptions\Alma_Api_Trigger_Payments;
use Alma\Woocommerce\Exceptions\Alma_Api_Full_Refund;
use Alma\Woocommerce\Exceptions\Alma_Api_Partial_Refund;
use Alma\Woocommerce\Exceptions\Alma_Wrong_Credentials;
use Alma\Woocommerce\Exceptions\Alma_Api_Plans;
use Alma\Woocommerce\Exceptions\Alma_Api_Merchants;
use Alma\Woocommerce\Exceptions\Alma_Activation;
use Alma\Woocommerce\Exceptions\Alma_Api_Share_Of_Checkout;

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
 * @property bool keys_validity Flag to indicate id the current keys are working
 * @property string selected_fee_plan Admin dashboard fee_plan in edition mode.
 * @property string test_merchant_id Alma TEST merchant ID
 * @property string live_merchant_id Alma LIVE merchant ID
 * @property string variable_product_price_query_selector Css query selector
 * @property string variable_product_sale_price_query_selector Css query selector for variable discounted products
 * @property string variable_product_check_variations_event JS event for product variation change
 * @property array excluded_products_list Wp Categories excluded slug's list
 * @property string share_of_checkout_enabled Bool for share of checkout acceptance (yes or no)
 * @property string share_of_checkout_enabled_date String Date when the marchand did accept the share of checkout
 */
class Alma_Settings {


	const OPTIONS_KEY = 'wc_alma_settings'; // Generated by WooCommerce in WC_Settings_API::get_option_key().

	/**
	 * Setting values from get_option.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Merchant available plans.
	 *
	 * @var array<FeePlan>
	 */
	public $allowed_fee_plans = array();

	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	public $logger;

	/**
	 * The api client.
	 *
	 * @var Alma\API\Client
	 */
	public $alma_client;


	/**
	 * Eligibilities
	 *
	 * @var Eligibility|Eligibility[]|array
	 */
	protected $eligibilities;
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Alma_Logger();

		$this->load_settings();
	}


	/**
	 * Load the DB settings and put it in variables.
	 *
	 * @return void
	 */
	public function load_settings() {
		$this->settings = self::get_settings();

		// Turn these settings into variables we can use.
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
	}

	/**
	 * Retrieve the db settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = (array) get_option( self::OPTIONS_KEY, array() );

		if ( ! empty( $settings['allowed_fee_plans'] ) && ! is_array( $settings['allowed_fee_plans'] ) ) {
			$settings['allowed_fee_plans'] = unserialize( $settings['allowed_fee_plans'] );
		}

		return array_merge( Alma_Helper_Settings::default_settings(), $settings );
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
	 * Gets enabled plans and configuration summary stored in settings for each enabled plan.
	 *
	 * @return array<array> containing arrays with plans configurations.
	 */
	public function get_enabled_plans_definitions() {
		$plans = array();

		if ( empty( $this->allowed_fee_plans ) ) {
			return $plans;
		}

		foreach ( $this->allowed_fee_plans as $fee_plan ) {
			$plan_key = $fee_plan->getPlanKey();

			if ( $this->is_plan_enabled( $plan_key ) ) {
				$plans[ $plan_key ] = array(
					'installments_count' => $this->get_installments_count( $plan_key ),
					'min_amount'         => $this->get_min_amount( $plan_key ),
					'max_amount'         => $this->get_max_amount( $plan_key ),
					'deferred_days'      => $this->get_deferred_days( $plan_key ),
					'deferred_months'    => $this->get_deferred_months( $plan_key ),
				);
			}
		}

		return $plans;
	}

	/**
	 * Is plan enabled.
	 *
	 * @param int $key plan key.
	 *
	 * @return bool
	 */
	protected function is_plan_enabled( $key ) {
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

		return apply_filters( 'alma_settings_' . $key, $value );
	}

	/**
	 * __set.
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
	public function get_i18n( $key ) {
		if ( Alma_Internationalization::is_site_multilingual() ) {
			if ( $this->{$key . '_' . get_locale()} ) {
				return $this->{$key . '_' . get_locale()};
			}

			return Alma_Internationalization::get_translated_text(
				Alma_Helper_Settings::default_settings()[ $key ],
				get_locale()
			);
		}

		return $this->{$key};
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
	 * Is using test API.
	 *
	 * @return bool
	 */
	public function is_test() {
		return $this->get_environment() === 'test';
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
	 * Check if we have keys for the active environment.
	 *
	 * @return bool
	 */
	public function has_keys() {
		if ( empty( $this->get_active_api_key() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Call the payment api and create.
	 *
	 * @param string $order_id The order id.
	 * @param array  $fee_plan_definition The plan definition.
	 *
	 * @return Payment
	 * @throws Alma_Api_Create_Payments Create payment exception.
	 */
	public function create_payments( $order_id, $fee_plan_definition ) {
		try {
			$model_payment = new Alma_Payment();

			$payload = $model_payment->get_payment_payload_from_order( $order_id, $fee_plan_definition );

			$this->get_alma_client();

			return $this->alma_client->payments->create( $payload );
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api create_payments, order id "%s" , Api message "%s"', $order_id, $e->getMessage() ) );
			throw new Alma_Api_Create_Payments( $order_id, $fee_plan_definition );
		}
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
	 * Get the alma api client.
	 *
	 * @return void
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws Alma_Exception General exception.
	 */
	public function get_alma_client() {
		if ( ! empty( $this->get_active_api_key() ) ) {

			$this->alma_client = new Client(
				$this->get_active_api_key(),
				array(
					'mode' => $this->get_environment(),
				)
			);

			$this->alma_client->addUserAgentComponent( 'WordPress', get_bloginfo( 'version' ) );
			$this->alma_client->addUserAgentComponent( 'WooCommerce', wc()->version );
			$this->alma_client->addUserAgentComponent( 'Alma for WooCommerce', ALMA_VERSION );

			return;
		}

		throw new Alma_Exception(
		// translators: %s: Error message.
			__( 'Alma encountered an error. No alma client found', 'alma-gateway-for-woocommerce' )
		);
	}

	/**
	 * Fetch the payment.
	 *
	 * @param string $payment_id The payment id.
	 *
	 * @return Payment
	 *
	 * @throws Alma_Api_Fetch_Payments Fetch payment exception.
	 */
	public function fetch_payment( $payment_id ) {
		try {
			$this->get_alma_client();

			return $this->alma_client->payments->fetch( $payment_id );

		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api fetch_payment, payment id "%s" , Api message "%s"', $payment_id, $e->getMessage() ) );
			throw new Alma_Api_Fetch_Payments( $payment_id );
		}
	}

	/**
	 * Share the data for soc.
	 *
	 * @param array $data   The payload.
	 *
	 * @throws Alma_Api_Share_Of_Checkout Alma_Api_Share_Of_Checkout exception.
	 */
	public function send_soc_data( $data ) {
		try {
			$this->get_alma_client();

			$this->alma_client->shareOfCheckout->share( $data );

		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api : shareOfCheckout, data : "%s", Api message "%s"', wp_json_encode( $data ), $e->getMessage() ) );
			throw new Alma_Api_Share_Of_Checkout( $data );
		}
	}

	/**
	 * Get the last soc date.
	 *
	 * @return mixed
	 *
	 * @throws Alma_Api_Soc_Last_Update_Dates The api exception.
	 */
	public function get_soc_last_updated_date() {
		try {
			$this->get_alma_client();

			return $this->alma_client->shareOfCheckout->getLastUpdateDates(); // phpcs:ignore
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api : getLastUpdateDates shareOfCheckout, Api message "%s"', $e->getMessage() ) );
			throw new Alma_Api_Soc_Last_Update_Dates();
		}
	}


	/**
	 * Sent the accept for the soc consent
	 *
	 * @throws Alma_Api_Share_Of_Checkout_Accept The exception.
	 */
	public function accept_soc_consent() {
		try {
			$this->get_alma_client();

			$this->alma_client->shareOfCheckout->addConsent();

		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api : accept share of shareOfCheckout, Api message "%s"', $e->getMessage() ) );
			throw new Alma_Api_Share_Of_Checkout_Accept();
		}
	}

	/**
	 * Sent the deny for the consent for soc.
	 *
	 * @throws Alma_Api_Share_Of_Checkout_Deny The exception.
	 */
	public function deny_soc_consent() {
		try {
			$this->get_alma_client();

			$this->alma_client->shareOfCheckout->removeConsent();

		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api : deny share of shareOfCheckout, Api message "%s"', $e->getMessage() ) );
			throw new Alma_Api_Share_Of_Checkout_Deny();
		}
	}


	/**
	 * Trigger the transaction.
	 *
	 * @param string $transaction_id The transaction id.
	 *
	 * @return Payment
	 *
	 * @throws Alma_Api_Trigger_Payments Api trigger exception.
	 */
	public function trigger_payment( $transaction_id ) {
		try {
			$this->get_alma_client();

			return $this->alma_client->payments->trigger( $transaction_id );

		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api trigger_payment, transaction id "%s" , Api message "%s"', $transaction_id, $e->getMessage() ) );
			throw new Alma_Api_Trigger_Payments( $transaction_id );
		}
	}

	/**
	 * Process of a full refund of a transaction.
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $merchant_reference The merchant reference.
	 * @param string $comment The comment.
	 *
	 * @return void
	 * @throws Alma_Api_Full_Refund APi refund exception.
	 */
	public function full_refund( $transaction_id, $merchant_reference, $comment ) {
		try {
			$this->get_alma_client();

			$this->alma_client->payments->fullRefund( $transaction_id, $merchant_reference, $comment );
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api full_refund, transaction id "%s" , Api message "%s"', $transaction_id, $e->getMessage() ) );
			throw new Alma_Api_Full_Refund( $transaction_id, $merchant_reference );
		}
	}

	/**
	 * Process of a partial refund
	 *
	 * @param string $transaction_id The transaction id.
	 * @param string $amount The amount.
	 * @param string $merchant_reference The merchant reference.
	 * @param string $comment The comment.
	 *
	 * @return void
	 * @throws Alma_Api_Partial_Refund APi refund exception.
	 */
	public function partial_refund( $transaction_id, $amount, $merchant_reference, $comment ) {
		try {
			$this->get_alma_client();

			$this->alma_client->payments->partialRefund( $transaction_id, $amount, $merchant_reference, $comment );
		} catch ( \Exception $e ) {
			$this->logger->error( sprintf( 'Api partialRefund, transaction id "%s" , Api message "%s"', $transaction_id, $e->getMessage() ) );
			throw new Alma_Api_Partial_Refund( $transaction_id, $merchant_reference, $amount );
		}
	}

	/**
	 * Flag as fraud.
	 *
	 * @param string $payment_id The payment id.
	 * @param string $reason The reason.
	 *
	 * @return bool
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws RequestError RequestError.
	 * @throws Alma_Exception Alma exception.
	 */
	public function flag_as_fraud( $payment_id, $reason ) {
		$this->get_alma_client();

		return $this->alma_client->payments->flagAsPotentialFraud( $payment_id, $reason );
	}

	/**
	 * Get the merchant id in DB.
	 *
	 * @return string
	 */
	public function get_active_merchant_id() {
		if ( $this->get_environment() === 'live' ) {
			return $this->live_merchant_id;
		}

		return $this->test_merchant_id;
	}

	/**
	 * Get the merchant id in api.
	 *
	 * @return void
	 * @throws Alma_Activation Alma_Activation.
	 * @throws Alma_Api_Merchants Alma_Api_Merchants.
	 * @throws Alma_Wrong_Credentials Alma_Wrong_Credentials.
	 * @throws Alma_Exception General exceptions.
	 */
	public function get_alma_merchant_id() {
		if ( ! empty( $this->alma_client ) ) {
			try {
				$this->{$this->environment . '_merchant_id'} = $this->alma_client->merchants->me()->id;
				$can_create_payment                          = $this->alma_client->merchants->me()->can_create_payments;

			} catch ( \Exception $e ) {
				$this->__set( 'keys_validity', 'no' );
				$this->save();

				if ( $e->response && 401 === $e->response->responseCode ) {
					throw new Alma_Wrong_Credentials( $this->get_environment() );
				}

				throw new Alma_Api_Merchants(
					// translators: %s: Error message.
					__( 'Alma encountered an error when fetching merchant status, please check your api keys or retry later.', 'alma-gateway-for-woocommerce' ),
					$e->getCode(),
					$e
				);
			}
			$this->__set( 'keys_validity', 'yes' );
			$this->save();

			if ( ! $can_create_payment ) {
				throw new Alma_Activation( $this->environment );
			}
		} else {
			throw new Alma_Exception(
			// translators: %s: Error message.
				__( 'Alma encountered an error.', 'alma-gateway-for-woocommerce' )
			);
		}
	}

	/**
	 * Get and manage the fee plans.
	 *
	 * @return void
	 * @throws Alma_Api_Plans Alma_Api_Plans.
	 */
	public function init_allowed_fee_plans() {
		$fee_plans = $this->get_alma_fee_plans();

		if ( ! count( $fee_plans ) ) {
			$message = __( 'Alma encountered an error when fetching the fee plans.', 'alma-gateway-for-woocommerce' );

			alma_plugin()->admin_notices->add_admin_notice( 'error_get_fee', 'notice notice-error', $message, true );

			throw new Alma_Api_Plans( $message );
		}

		$this->allowed_fee_plans = array_filter(
			$fee_plans,
			function ( $fee_plan ) {
				return $this->is_allowed_fee_plan( $fee_plan );
			}
		);

		$this->settings['allowed_fee_plans'] = serialize( $this->allowed_fee_plans );

		foreach ( $this->allowed_fee_plans as $fee_plan ) {
			$plan_key           = $fee_plan->getPlanKey();
			$default_min_amount = $fee_plan->min_purchase_amount;
			$default_max_amount = $fee_plan->max_purchase_amount;
			$min_key            = "min_amount_$plan_key";
			$max_key            = "max_amount_$plan_key";
			$enabled_key        = "enabled_$plan_key";

			if ( ! isset( $this->settings[ $min_key ] ) || $this->settings[ $min_key ] < $default_min_amount || $this->settings[ $min_key ] > $default_max_amount ) {
				$this->settings[ $min_key ] = $default_min_amount;
			}
			if ( ! isset( $this->settings[ $max_key ] ) || $this->settings[ $max_key ] > $default_max_amount || $this->settings[ $max_key ] < $default_min_amount ) {
				$this->settings[ $max_key ] = $default_max_amount;
			}
			if ( ! isset( $this->settings[ $enabled_key ] ) ) {
				$this->settings[ $enabled_key ] = Alma_Constants::DEFAULT_FEE_PLAN === $plan_key ? 'yes' : 'no';
			}
			$this->settings[ "deferred_months_$plan_key" ]    = $fee_plan->getDeferredMonths();
			$this->settings[ "deferred_days_$plan_key" ]      = $fee_plan->getDeferredDays();
			$this->settings[ "installments_count_$plan_key" ] = $fee_plan->getInstallmentsCount();
		}
	}


	/**
	 * Retrieves the api fee plans.
	 *
	 * @return FeePlan[]
	 */
	public function get_alma_fee_plans() {
		try {
			return $this->alma_client->merchants->feePlans( FeePlan::KIND_GENERAL, 'all', true );
		} catch ( \Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getTrace() );
		}

		return array();
	}

	/**
	 * Says if a fee_plan is allowed or not based on Alma fee plans settings & business rules.
	 *
	 * @param FeePlan $fee_plan as fee_plan to evaluate.
	 *
	 * @return bool
	 */
	protected function is_allowed_fee_plan( FeePlan $fee_plan ) {
		if ( ! $fee_plan->allowed ) {
			return false;
		}
		if ( $fee_plan->isPayLaterOnly() || $fee_plan->isPnXOnly() ) {
			return true;
		}

		return false;
	}

	/**
	 * Is Alma available for this user ?
	 *
	 * @param \WP_User $user The user roles which to test.
	 *
	 * @return bool
	 */
	public function is_allowed_to_see_alma( \WP_User $user ) {
		return in_array( 'administrator', $user->roles, true ) || 'live' === $this->get_environment();
	}

	/**
	 * Add two alma payment gateways if needed (pay_later and pnx_plus_4)
	 *
	 * Fields "title" and "description" will then be overwritten by filters :
	 * "woocommerce_gateway_title" and "woocommerce_gateway_description".
	 *
	 * @param object $gateway Alma WC payment gateway.
	 *
	 * @return array
	 */
	public function build_new_available_gateways( $gateway ) {
		$new_available_gateways = array();

		if ( $this->is_there_available_plan_for_this_gateway( Alma_Constants::ALMA_GATEWAY_PAY_LATER ) ) {
			$tmp_gateway                                = clone $gateway;
			$tmp_gateway->id                            = Alma_Constants::ALMA_GATEWAY_PAY_LATER;
			$new_available_gateways[ $tmp_gateway->id ] = $tmp_gateway;
		}

		if ( $this->is_there_available_plan_for_this_gateway( Alma_Constants::ALMA_GATEWAY_PAY_MORE_THAN_FOUR ) ) {
			$tmp_gateway                                = clone $gateway;
			$tmp_gateway->id                            = Alma_Constants::ALMA_GATEWAY_PAY_MORE_THAN_FOUR;
			$new_available_gateways[ $tmp_gateway->id ] = $tmp_gateway;
		}

		return $new_available_gateways;
	}

	/**
	 * Test if is there available plan for given payment method
	 *
	 * @param string $gateway_id As payment method name.
	 *
	 * @return bool
	 */
	protected function is_there_available_plan_for_this_gateway( $gateway_id ) {
		foreach ( $this->get_eligible_plans_keys_for_cart() as $plan_key ) {
			if ( $this->should_display_plan( $plan_key, $gateway_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get eligible plans keys for current cart.
	 *
	 * @return string[]
	 */
	public function get_eligible_plans_keys_for_cart() {
		$cart_eligibilities = $this->get_cart_eligibilities();

		return array_filter(
			$this->get_eligible_plans_keys( ( new Alma_Cart() )->get_total_in_cents() ),
			function ( $key ) use ( $cart_eligibilities ) {
				if ( is_array( $cart_eligibilities ) ) {
					return array_key_exists( $key, $cart_eligibilities );
				}

				return property_exists( $cart_eligibilities, $key );
			}
		);
	}

	/**
	 * Get eligibilities from cart.
	 *
	 * @return Eligibility|Eligibility[]|array
	 */
	public function get_cart_eligibilities() {
		if ( ! $this->eligibilities ) {

			try {
				$this->get_alma_client();
				$this->eligibilities = $this->alma_client->payments->eligibility( Alma_Payment::get_eligibility_payload_from_cart() );
			} catch ( \Exception $error ) {
				$this->logger->error( $error->getMessage(), $error->getTrace() );

				return array();
			}
		}

		return $this->eligibilities;
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
	 * Checks if a plan is eligible.
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
	 * Tells if we should display this fee plan for this gateway. (we have three alma payment gateways)
	 *
	 * @param string $plan_key Plan key.
	 * @param string $gateway_id Gateway id.
	 *
	 * @return bool
	 */
	public function should_display_plan( $plan_key, $gateway_id ) {
		switch ( $gateway_id ) {
			case Alma_Constants::GATEWAY_ID:
				$should_display = in_array(
					$this->get_installments_count( $plan_key ),
					array(
						2,
						3,
						4,
					),
					true
				);
				break;
			case Alma_Constants::ALMA_GATEWAY_PAY_LATER:
				$should_display = (
					$this->get_installments_count( $plan_key ) === 1
					&& ( $this->get_deferred_days( $plan_key ) !== 0 || $this->get_deferred_months( $plan_key ) !== 0 )
				);
				break;
			case Alma_Constants::ALMA_GATEWAY_PAY_MORE_THAN_FOUR:
				$should_display = ( $this->get_installments_count( $plan_key ) > 4 );
				break;
			default:
				return false;
		}

		return $should_display;
	}

	/**
	 * Get Eligibility / Payment formatted eligible plans definitions for current cart.
	 *
	 * @return array<array>
	 */
	public function get_eligible_plans_for_cart() {
		$amount = ( new Alma_Cart() )->get_total_in_cents();

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
				$this->get_eligible_plans_definitions( $amount )
			)
		);
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
	 * Returns the list of texts proposed to be displayed on front-office.
	 *
	 * @return string
	 */
	public function get_display_text() {
		return Alma_General::get_display_texts_keys_and_values() [ $this->payment_upon_trigger_display_text ];
	}

	/**
	 * Populate array with plan settings.
	 *
	 * @param string $plan_key The plan key.
	 *
	 * @return array
	 * @throws Alma_Plans_Definition Alma_Plans_Definition.
	 */
	public function get_fee_plan_definition( $plan_key ) {

		$definition = array();

		if ( ! isset( $this->settings[ "installments_count_$plan_key" ] ) ) {
			throw new Alma_Plans_Definition( "installments_count_$plan_key not set" );
		}

		if ( ! isset( $this->settings[ "deferred_days_$plan_key" ] ) ) {
			throw new Alma_Plans_Definition( "deferred_days_$plan_key not set" );
		}

		if ( ! isset( $this->settings[ "deferred_months_$plan_key" ] ) ) {
			throw new Alma_Plans_Definition( "deferred_months_$plan_key not set" );
		}

		$definition['installments_count'] = $this->settings[ "installments_count_$plan_key" ];
		$definition['deferred_days']      = $this->settings[ "deferred_days_$plan_key" ];
		$definition['deferred_months']    = $this->settings[ "deferred_months_$plan_key" ];

		return $definition;
	}

	/**
	 * Does need API key ?
	 *
	 * @return bool
	 */
	public function need_api_key() {
		return empty( $this->get_active_api_key() );
	}
}
