<?php

namespace Alma\Gateway\Infrastructure\Gateway\Backend;

use Alma\Gateway\Application\Entity\FeePlansConfigForm;
use Alma\Gateway\Application\Entity\KeysConfigForm;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigFormService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * It's a fake gateway that is used to configure the plugin.
 */
class AlmaGateway extends AbstractBackendGateway {

	public const GATEWAY_TYPE = 'config';


	/**
	 * Gateway constructor.
	 * @throws GatewayServiceException
	 */
	public function __construct() {
		$this->method_title       = L10nHelper::__( 'Payment in installments and deferred with Alma' );
		$this->method_description = L10nHelper::__( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.' );
		$this->has_fields         = true;
		parent::__construct();

		// Define filters for sanitizing settings
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Is gateway available?
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * Initialize form fields.
	 * @throws FeePlanRepositoryException
	 */
	public function init_form_fields() {

		// Initialize minimum form fields
		$this->form_fields = array_merge(
			$this->form_fields,
			$this->enabled_field(),
			$this->api_key_fieldset(),
		);

		// If the plugin is configured, add the gateway and fee plan fields
		if ( PluginHelper::isConfigured() ) {
			$this->form_fields = array_merge(
				$this->form_fields,
				$this->widget_fieldset(),
				$this->excluded_categories_fieldset(),
				$this->gateway_order_fieldset(),
				$this->fee_plan_fieldset()
			);
		}

		$this->form_fields = array_merge(
			$this->form_fields,
			$this->debug_fieldset(),
			$this->l10n_fieldset(),
		);

		return $this->form_fields;
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		parent::init_settings();

		/** @var EncryptorHelper $encryptor_helper */
		$encryptor_helper = Plugin::get_container()->get( EncryptorHelper::class );

		if ( ! empty( $this->settings[ self::FIELD_LIVE_API_KEY ] ) ) {
			$this->settings[ self::FIELD_LIVE_API_KEY ] = $encryptor_helper->decrypt( $this->settings[ self::FIELD_LIVE_API_KEY ] );
		}

		if ( ! empty( $this->settings[ self::FIELD_TEST_API_KEY ] ) ) {
			$this->settings[ self::FIELD_TEST_API_KEY ] = $encryptor_helper->decrypt( $this->settings[ self::FIELD_TEST_API_KEY ] );
		}
	}

	/**
	 * Sanitize our settings
	 * This method is called by WooCommerce when saving the settings.
	 * It allows us to sanitize the settings before they are saved in the database.
	 *
	 * @param array $settings The settings to sanitize.
	 *
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( array $settings ): array {

		// Clean settings
		$settings = $this->clean_decoration_fields( $settings );

		/** @var ConfigFormService $config_form_service */
		$config_form_service = Plugin::get_container()->get( ConfigFormService::class );
		/** @var ConfigService $config_service */
		$config_service = Plugin::get_container()->get( ConfigService::class );

		// Create a config form to validate form data
		$keys_config_form = ( new KeysConfigForm( $config_service ) )
			->setNewTestKey( $settings[ self::FIELD_TEST_API_KEY ] )
			->setNewLiveKey( $settings[ self::FIELD_LIVE_API_KEY ] );

		$fee_plans_config_form = ( new FeePlansConfigForm( $config_service ) );
		foreach ( $this->extractFeePlanFromForm( $settings ) as $key => $value ) {
			$fee_plans_config_form->addFeePlan(
				$key,
				$value[ AbstractBackendGateway::MIN_AMOUNT_SUFFIX ],
				$value[ AbstractBackendGateway::MAX_AMOUNT_SUFFIX ],
				$value[ AbstractBackendGateway::ENABLED_SUFFIX ]
			);
		}

		// Check if the config form is valid
		$keys_config_form      = $config_form_service->checkKeysForm( $keys_config_form );
		$fee_plans_config_form = $config_form_service->checkFeePlansForm( $fee_plans_config_form );

		// Add errors to the gateway
		$this->errors = array_merge(
			$this->errors,
			$keys_config_form->getErrors(),
			$fee_plans_config_form->getErrors()
		);

		$settings[ self::FIELD_TEST_API_KEY ] = $keys_config_form->getNewTestKey();
		$settings[ self::FIELD_LIVE_API_KEY ] = $keys_config_form->getNewLiveKey();
		$settings[ self::FIELD_MERCHANT_ID ]  = $keys_config_form->getNewMerchantId();
		foreach ( $fee_plans_config_form->getFeePlans() as $planKey => $feePlanArray ) {
			$settings[ $planKey . '_' . AbstractBackendGateway::MIN_AMOUNT_SUFFIX ] = $feePlanArray[ FeePlansConfigForm::FEE_PLAN_MIN_AMOUNT_KEY ];
			$settings[ $planKey . '_' . AbstractBackendGateway::MAX_AMOUNT_SUFFIX ] = $feePlanArray[ FeePlansConfigForm::FEE_PLAN_MAX_AMOUNT_KEY ];
			$settings[ $planKey . '_' . AbstractBackendGateway::ENABLED_SUFFIX ]    = $feePlanArray[ FeePlansConfigForm::FEE_PLAN_ENABLED_KEY ];
		}

		return $settings;
	}

	/**
	 * Process admin Options and display errors
	 *
	 * @return bool
	 */
	public function process_admin_options(): bool {
		$saved = parent::process_admin_options();

		$this->display_errors();

		return $saved;
	}

	private function extractFeePlanFromForm( array $settings ) {

		// Group pairs of min_amount and max_amount
		$groups = array();

		foreach ( $settings as $key => $value ) {
			$pattern = '/^(.*)_(' . AbstractBackendGateway::MIN_AMOUNT_SUFFIX . '|' . AbstractBackendGateway::MAX_AMOUNT_SUFFIX . ')$/';
			if ( preg_match( $pattern, $key, $matches ) ) {
				$groups[ $matches[1] ][ $matches[2] ] = DisplayHelper::price_to_cent( $value );
			}
			$pattern = '/^(' . AbstractBackendGateway::ENABLED_PREFIX . '.*)_(' . AbstractBackendGateway::ENABLED_SUFFIX . ')$/';
			if ( preg_match( $pattern, $key, $matches ) ) {
				$groups[ $matches[1] ][ $matches[2] ] = $value;
			}
		}

		return $groups;
	}

	/**
	 * Remove decoration fields from settings.
	 * This is used to avoid saving unnecessary fields in the database.
	 * Natively, WooCommerce saves all fields but titles, including those that are not used.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	private function clean_decoration_fields( array $settings ): array {
		// Remove decoration fields
		unset(
			$settings['gateway_footer'],
			$settings['gateway_header'],
			$settings['fee_plan_footer'],
			$settings['fee_plan_header'],
			$settings['debug_section'],
			$settings['keys_section'],
			$settings['l10n_section'],
		);

		return $settings;
	}
}
