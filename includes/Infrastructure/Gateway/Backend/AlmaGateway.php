<?php

namespace Alma\Gateway\Infrastructure\Gateway\Backend;

use Alma\Gateway\Application\Entity\Form\GatewayConfigurationForm;
use Alma\Gateway\Application\Exception\Service\GatewayConfigurationFormValidatorServiceException;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\GatewayConfigurationFormValidatorService;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Mapper\ConfigFormMapper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * It's a fake gateway that is used to configure the plugin.
 */
class AlmaGateway extends AbstractBackendGateway {

	public const PAYMENT_METHOD = 'config';


	/**
	 * Gateway constructor.
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

		if ( ! empty( $this->settings[ GatewayConfigurationForm::FIELD_LIVE_API_KEY ] ) ) {
			$this->settings[ GatewayConfigurationForm::FIELD_LIVE_API_KEY ] = $encryptor_helper->decrypt( $this->settings[ GatewayConfigurationForm::FIELD_LIVE_API_KEY ] );
		}

		if ( ! empty( $this->settings[ GatewayConfigurationForm::FIELD_TEST_API_KEY ] ) ) {
			$this->settings[ GatewayConfigurationForm::FIELD_TEST_API_KEY ] = $encryptor_helper->decrypt( $this->settings[ GatewayConfigurationForm::FIELD_TEST_API_KEY ] );
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

		// Transform settings to GatewayConfiguration
		/** @var ConfigFormMapper $config_form_mapper */
		$config_form_mapper    = Plugin::get_container()->get( ConfigFormMapper::class );
		$gateway_configuration = $config_form_mapper->from_cms_form( $settings );

		// Validate settings
		try {
			/** @var GatewayConfigurationFormValidatorService $config_form_validator_service */
			$config_form_validator_service = Plugin::get_container()->get( GatewayConfigurationFormValidatorService::class );
			$gateway_configuration         = $config_form_validator_service->validate( $gateway_configuration );
		} catch ( GatewayConfigurationFormValidatorServiceException $e ) {
			// If an error occurs during validation, we display a generic error message
			// and return the previous settings to avoid losing data.
			$this->errors = array( L10nHelper::__( 'An error occurred while validating the configuration. Please try again.' ) );

			/** @var ConfigService $config_service */
			$config_service = Plugin::get_container()->get( ConfigService::class );

			return $config_service->getSettings();
		}

		// Transform back to settings array
		$settings = $config_form_mapper->to_cms_form( $gateway_configuration );

		// Add errors to the gateway
		$this->errors = array_merge(
			$this->errors,
			$gateway_configuration->getErrors()
		);

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
