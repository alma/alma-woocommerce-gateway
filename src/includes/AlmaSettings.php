<?php
/**
 * AlmaSettings.
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
use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Payment;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder;
use Alma\Woocommerce\Exceptions\ActivationException;
use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Exceptions\ApiCreatePaymentsException;
use Alma\Woocommerce\Exceptions\ApiFetchPaymentsException;
use Alma\Woocommerce\Exceptions\ApiFullRefundException;
use Alma\Woocommerce\Exceptions\ApiMerchantsException;
use Alma\Woocommerce\Exceptions\ApiPartialRefundException;
use Alma\Woocommerce\Exceptions\ApiPlansException;
use Alma\Woocommerce\Exceptions\ApiShareOfCheckoutAcceptException;
use Alma\Woocommerce\Exceptions\ApiShareOfCheckoutDenyException;
use Alma\Woocommerce\Exceptions\ApiShareOfCheckoutException;
use Alma\Woocommerce\Exceptions\ApiSocLastUpdateDatesException;
use Alma\Woocommerce\Exceptions\ApiTriggerPaymentsException;
use Alma\Woocommerce\Exceptions\PlansDefinitionException;
use Alma\Woocommerce\Exceptions\WrongCredentialsException;
use Alma\Woocommerce\Factories\PluginFactory;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\EncryptorHelper;
use Alma\Woocommerce\Helpers\FeePlanHelper;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\SettingsHelper;
use Exception;
use WC_Order;
use WP_User;

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
 * @property bool   keys_validity Flag to indicate id the current keys are working
 * @property string selected_fee_plan Admin dashboard fee_plan in edition mode.
 * @property string test_merchant_id Alma TEST merchant ID
 * @property string test_merchant_name Alma TEST merchant name
 * @property string live_merchant_id Alma LIVE merchant ID
 * @property string live_merchant_name Alma LIVE merchant name
 * @property string variable_product_price_query_selector Css query selector
 * @property string variable_product_sale_price_query_selector Css query selector for variable discounted products
 * @property string variable_product_check_variations_event JS event for product variation change
 * @property array  excluded_products_list Wp Categories excluded slug's list
 * @property string share_of_checkout_enabled Bool for share of checkout acceptance (yes or no)
 * @property string share_of_checkout_enabled_date String Date when the merchant did accept the share of checkout
 * @property string share_of_checkout_last_sharing_date String Date when we sent the data to Alma
 * @property bool   display_in_page Bool if In Page is activated
 * @property bool   use_blocks_template Bool if we want to use a blocks template
 */
class AlmaSettings {


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
	 * @var AlmaLogger
	 */
	public $logger;

	/**
	 * The api client.
	 *
	 * @var Client
	 */
	public $alma_client;


	/**
	 * The encryptor.
	 *
	 * @var EncryptorHelper
	 */
	public $encryptor_helper;


	/**
	 * The fee plan helper.
	 *
	 * @var FeePlanHelper
	 */
	public $fee_plan_helper;

	/**
	 * Internationalization Helper.
	 *
	 * @var InternationalizationHelper
	 */
	protected $internationalization_helper;

	/**
	 * Settings Helper.
	 *
	 * @var SettingsHelper
	 */
	protected $settings_helper;

	/**
	 * The version factory.
	 *
	 * @var VersionFactory
	 */
	protected $version_factory;


	/**
	 * The plugin factory.
	 *
	 * @var PluginFactory
	 */
	protected $plugin_factory;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger                      = new AlmaLogger();
		$this->encryptor_helper            = new EncryptorHelper();
		$this->fee_plan_helper             = new FeePlanHelper();
		$this->internationalization_helper = new InternationalizationHelper();
		$this->version_factory             = new VersionFactory();
		$this->plugin_factory              = new PluginFactory();

		$settings_helper_builder = new SettingsHelperBuilder();
		$this->settings_helper   = $settings_helper_builder->get_instance();

		$this->load_settings();
	}


	/**
	 * Load the DB settings and put it in variables.
	 *
	 * @return void
	 */
	public function load_settings() {
		$this->settings = $this->get_settings();

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
	public function get_settings() {
		$settings = (array) get_option( self::OPTIONS_KEY, array() );

		if ( ! empty( $settings['allowed_fee_plans'] ) && ! is_array( $settings['allowed_fee_plans'] ) ) {
			$settings['allowed_fee_plans'] = unserialize( $settings['allowed_fee_plans'] ); // phpcs:ignore
		}

		return array_merge( $this->settings_helper->default_settings(), $settings );
	}

	/**
	 * Is blocks template enabled.
	 *
	 * @return bool
	 */
	public function is_blocks_template_enabled() {
		return 'yes' === $this->use_blocks_template;
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

		uksort( $plans, array( $this->fee_plan_helper, 'alma_usort_plans_keys' ) );

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
	 * Is the plan a pnx plus 4 ?
	 *
	 * @param FeePlan $fee_plan The fee plan.
	 *
	 * @return bool
	 */
	public function is_pnx_plus_4( $fee_plan ) {
		if ( $fee_plan->getInstallmentsCount() > 4 ) {
			return true;
		}

		return false;
	}

	/**
	 * Tells if the merchant has pay now payment method enabled in the WC back-office.
	 *
	 * @return bool
	 */
	public function has_pay_now() {
		foreach ( $this->get_enabled_plans_definitions() as $plan_definition ) {
			if ( 1 === $plan_definition['installments_count'] ) {
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
	 * @param string $is_blocks Are we in blocks.
	 *
	 * @return string
	 */
	public function get_title( $payment_method, $is_blocks = false ) {
		if ( $is_blocks ) {
			return $this->get_i18n( 'title_blocks_' . $payment_method );
		}

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
		if ( $this->internationalization_helper->is_site_multilingual() ) {
			if ( $this->{$key . '_' . get_locale()} ) {
				return $this->{$key . '_' . get_locale()};
			}

			return $this->internationalization_helper->get_translated_text(
				$this->settings_helper->default_settings()[ $key ],
				get_locale()
			);
		}

		return $this->{$key};
	}

	/**
	 * Gets title for a payment method.
	 *
	 * @param string $payment_method The payment method.
	 * @param string $is_blocks Are we in blocks.
	 *
	 * @return string
	 */
	public function get_description( $payment_method, $is_blocks = false ) {
		if ( $is_blocks ) {
			return $this->get_i18n( 'description_blocks_' . $payment_method );
		}

		return $this->get_i18n( 'description_' . $payment_method );
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
	 * Gets API key for live environment.
	 *
	 * @return string
	 */
	public function get_live_api_key() {
		return $this->encryptor_helper->decrypt( $this->live_api_key );
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string
	 */
	public function get_test_api_key() {
		return $this->encryptor_helper->decrypt( $this->test_api_key );
	}

	/**
	 * Fetch the payment.
	 *
	 * @param string $payment_id The payment id.
	 *
	 * @return Payment
	 *
	 * @throws ApiFetchPaymentsException Fetch payment exception.
	 */
	public function fetch_payment( $payment_id ) {
		try {
			$this->get_alma_client();

			return $this->alma_client->payments->fetch( $payment_id );

		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api fetch_payment, payment id "%s" , Api message "%s"', $payment_id, $e->getMessage() ) );
			throw new ApiFetchPaymentsException( $payment_id );
		}
	}

	/**
	 * Get the alma api client.
	 *
	 * @return void
	 * @throws DependenciesError DependenciesError.
	 * @throws ParamsError ParamsError.
	 * @throws AlmaException General exception.
	 */
	public function get_alma_client() {
		if ( ! empty( $this->get_active_api_key() ) ) {

			$this->alma_client = new Client(
				$this->get_active_api_key(),
				array(
					'mode'   => $this->get_environment(),
					'logger' => $this->logger,
				)
			);

			$this->alma_client->addUserAgentComponent( 'WordPress', get_bloginfo( 'version' ) );
			$this->alma_client->addUserAgentComponent( 'WooCommerce', $this->version_factory->get_version() );
			$this->alma_client->addUserAgentComponent( 'Alma for WooCommerce', ALMA_VERSION );

			return;
		}

		throw new AlmaException(
		// translators: %s: Error message.
			__( 'Alma encountered an error. No alma client found', 'alma-gateway-for-woocommerce' )
		);
	}

	/**
	 * Create the payment.
	 *
	 * @param array    $payload The payload.
	 * @param WC_Order $wc_order The order id.
	 * @param FeePlan  $fee_plan The fee plan.
	 *
	 * @return Payment
	 *
	 * @throws ApiCreatePaymentsException Create payment exception.
	 */
	public function create_payment( $payload, $wc_order, $fee_plan ) {
		try {
			$this->get_alma_client();

			return $this->alma_client->payments->create( $payload );

		} catch ( Exception $e ) {
			$this->logger->error(
				sprintf(
					'Api create_payments, order id "%s" , Api message "%s", Payload "%s", Trace "%s"',
					$wc_order->get_id(),
					$e->getMessage(),
					wp_json_encode( $payload ),
					$e->getTraceAsString()
				)
			);
			throw new ApiCreatePaymentsException( $wc_order->get_id(), $fee_plan, $payload );
		}
	}

	/**
	 * Share the data for soc.
	 *
	 * @param array $data The payload.
	 *
	 * @throws ApiShareOfCheckoutException ApiShareOfCheckoutException exception.
	 */
	public function send_soc_data( $data ) {
		try {
			$this->get_alma_client();

			$this->alma_client->shareOfCheckout->share( $data );

		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api : shareOfCheckout, data : "%s", Api message "%s"', wp_json_encode( $data ), $e->getMessage() ) );
			throw new ApiShareOfCheckoutException( $data );
		}
	}

	/**
	 * Get the last soc date.
	 *
	 * @return array
	 *
	 * @throws ApiSocLastUpdateDatesException The api exception.
	 */
	public function get_soc_last_updated_date() {
		try {
			$this->get_alma_client();

			return $this->alma_client->shareOfCheckout->getLastUpdateDates(); // phpcs:ignore
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api : getLastUpdateDates shareOfCheckout, Api message "%s"', $e->getMessage() ) );
			throw new ApiSocLastUpdateDatesException();
		}
	}

	/**
	 * Sent the accept for the soc consent
	 *
	 * @throws ApiShareOfCheckoutAcceptException The exception.
	 */
	public function accept_soc_consent() {
		try {
			$this->get_alma_client();

			$this->alma_client->shareOfCheckout->addConsent();

		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api : accept share of shareOfCheckout, Api message "%s"', $e->getMessage() ) );
			throw new ApiShareOfCheckoutAcceptException();
		}
	}

	/**
	 * Sent the deny for the consent for soc.
	 *
	 * @throws ApiShareOfCheckoutDenyException The exception.
	 */
	public function deny_soc_consent() {
		try {
			$this->get_alma_client();

			$this->alma_client->shareOfCheckout->removeConsent();

		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api : deny share of shareOfCheckout, Api message "%s"', $e->getMessage() ) );
			throw new ApiShareOfCheckoutDenyException();
		}
	}

	/**
	 * Trigger the transaction.
	 *
	 * @param string $transaction_id The transaction id.
	 *
	 * @return Payment
	 *
	 * @throws ApiTriggerPaymentsException Api trigger exception.
	 */
	public function trigger_payment( $transaction_id ) {
		try {
			$this->get_alma_client();

			return $this->alma_client->payments->trigger( $transaction_id );

		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api trigger_payment, transaction id "%s" , Api message "%s"', $transaction_id, $e->getMessage() ) );
			throw new ApiTriggerPaymentsException( $transaction_id );
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
	 * @throws ApiFullRefundException APi refund exception.
	 */
	public function full_refund( $transaction_id, $merchant_reference, $comment ) {
		try {
			$this->get_alma_client();

			$this->alma_client->payments->fullRefund( $transaction_id, $merchant_reference, $comment );
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api full_refund, transaction id "%s" , Api message "%s"', $transaction_id, $e->getMessage() ) );
			throw new ApiFullRefundException( $transaction_id, $merchant_reference );
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
	 * @throws ApiPartialRefundException APi refund exception.
	 */
	public function partial_refund( $transaction_id, $amount, $merchant_reference, $comment ) {
		try {
			$this->get_alma_client();

			$this->alma_client->payments->partialRefund( $transaction_id, $amount, $merchant_reference, $comment );
		} catch ( Exception $e ) {
			$this->logger->error( sprintf( 'Api partialRefund, transaction id "%s" , Api message "%s"', $transaction_id, $e->getMessage() ) );
			throw new ApiPartialRefundException( $transaction_id, $merchant_reference, $amount );
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
	 * @throws AlmaException Alma exception.
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
	 * @throws ActivationException ActivationException.
	 * @throws ApiMerchantsException ApiMerchantsException.
	 * @throws WrongCredentialsException WrongCredentialsException.
	 * @throws AlmaException General exceptions.
	 * @throws DependenciesError Dependencies exceptions.
	 * @throws ParamsError Params exceptions.
	 */
	public function get_alma_merchant_id() {

		$this->get_alma_client();

		if ( ! empty( $this->alma_client ) ) {
			try {
				$merchant                                      = $this->alma_client->merchants->me();
				$this->{$this->environment . '_merchant_id'}   = $merchant->id;
				$this->{$this->environment . '_merchant_name'} = $merchant->name;
			} catch ( Exception $e ) {
				$this->__set( 'keys_validity', 'no' );

				$this->save();

				if ( $e->response && 401 === $e->response->responseCode ) {
					throw new WrongCredentialsException( $this->get_environment() );
				}

				throw new ApiMerchantsException(
				// translators: %s: Error message.
					__( 'Alma encountered an error when fetching merchant status, please check your api keys or retry later.', 'alma-gateway-for-woocommerce' ),
					$e->getCode(),
					$e
				);
			}

			$this->__set( 'keys_validity', 'yes' );
			$this->save();

			if ( ! $merchant->can_create_payments ) {
				throw new ActivationException( $this->environment );
			}
		} else {
			throw new AlmaException(
			// translators: %s: Error message.
				__( 'Alma encountered an error.', 'alma-gateway-for-woocommerce' )
			);
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
	 * Get and manage the fee plans.
	 *
	 * @return void
	 * @throws ApiPlansException ApiPlansException.
	 */
	public function init_allowed_fee_plans() {
		$fee_plans = $this->get_alma_fee_plans();

		if ( ! count( $fee_plans ) ) {
			$message = __( 'Alma encountered an error when fetching the fee plans.', 'alma-gateway-for-woocommerce' );

			$this->plugin_factory->add_admin_notice( 'error_get_fee', 'notice notice-error', $message, true );

			throw new ApiPlansException( $message );
		}

		$this->allowed_fee_plans = array_filter(
			$fee_plans,
			function ( $fee_plan ) {
				return $this->is_allowed_fee_plan( $fee_plan );
			}
		);

		$this->settings['allowed_fee_plans'] = serialize( $this->allowed_fee_plans ); // phpcs:ignore

		foreach ( $this->allowed_fee_plans as $fee_plan ) {
			$plan_key           = $fee_plan->getPlanKey();
			$default_min_amount = $this->fee_plan_helper->get_min_purchase_amount( $fee_plan );
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
				$this->settings[ $enabled_key ] = ConstantsHelper::DEFAULT_FEE_PLAN === $plan_key ? 'yes' : 'no';
			}
			$this->settings["deferred_months_$plan_key"]    = $fee_plan->getDeferredMonths();
			$this->settings["deferred_days_$plan_key"]      = $fee_plan->getDeferredDays();
			$this->settings["installments_count_$plan_key"] = $fee_plan->getInstallmentsCount();
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
		} catch ( Exception $e ) {
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
		if (
			$fee_plan->isPayLaterOnly()
			|| $fee_plan->isPnXOnly()
			|| $fee_plan->isPayNow()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Is Alma available for this user ?
	 *
	 * @param WP_User $user The user roles which to test.
	 *
	 * @return bool
	 */
	public function is_allowed_to_see_alma( WP_User $user ) {
		return in_array( 'administrator', $user->roles, true ) || 'live' === $this->get_environment();
	}

	/**
	 * Tells if the merchant has at least one "pnx_plus_4" payment method enabled in the WC back-office.
	 *
	 * @return bool
	 */
	public function has_pnx_4() {
		foreach ( $this->get_enabled_plans_definitions() as $plan_definition ) {
			if ( $plan_definition['installments_count'] <= 4 && $plan_definition['installments_count'] > 1 ) {
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
			case ConstantsHelper::GATEWAY_ID:
			case ConstantsHelper::GATEWAY_ID_IN_PAGE:
				$display_plan = in_array(
					$this->get_installments_count( $plan_key ),
					array(
						2,
						3,
						4,
					),
					true
				);
				break;
			case ConstantsHelper::GATEWAY_ID_PAY_NOW:
			case ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW:
				$display_plan = $this->get_installments_count( $plan_key ) === 1
				                && ( $this->get_deferred_days( $plan_key ) === 0 && $this->get_deferred_months( $plan_key ) === 0 );
				break;
			case ConstantsHelper::GATEWAY_ID_PAY_LATER:
			case ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER:
				$display_plan = $this->get_installments_count( $plan_key ) === 1
				                && ( $this->get_deferred_days( $plan_key ) !== 0 || $this->get_deferred_months( $plan_key ) !== 0 );
				break;
			case ConstantsHelper::GATEWAY_ID_IN_PAGE_MORE_THAN_FOUR:
			case ConstantsHelper::GATEWAY_ID_MORE_THAN_FOUR:
				$display_plan = $this->get_installments_count( $plan_key ) > 4;
				break;
			default:
				$display_plan = false;
		}

		return $display_plan;
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
		return $this->internationalization_helper->get_display_texts_keys_and_values() [ $this->payment_upon_trigger_display_text ];
	}

	/**
	 * /**
	 * Populate fee plan
	 *
	 * @param string $plan_key The plan key.
	 *
	 * @return FeePlan
	 * @throws PlansDefinitionException PlansDefinitionException.
	 */
	public function build_fee_plan( $plan_key ) {

		if ( ! isset( $this->settings["installments_count_$plan_key"] ) ) {
			throw new PlansDefinitionException( "installments_count_$plan_key not set" );
		}

		if ( ! isset( $this->settings["deferred_days_$plan_key"] ) ) {
			throw new PlansDefinitionException( "deferred_days_$plan_key not set" );
		}

		if ( ! isset( $this->settings["deferred_months_$plan_key"] ) ) {
			throw new PlansDefinitionException( "deferred_months_$plan_key not set" );
		}

		return new FeePlan(
			array(
				'installments_count' => $this->settings["installments_count_$plan_key"],
				'deferred_days'      => $this->settings["deferred_days_$plan_key"],
				'deferred_months'    => $this->settings["deferred_months_$plan_key"],
			)
		);
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
