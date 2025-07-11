<?php

namespace Alma\Gateway\Business\Gateway\Backend;

use Alma\API\ClientConfiguration;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\AuthenticationService;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * It's a fake gateway that is used to configure the plugin.
 */
class AlmaGateway extends AbstractBackendGateway {

	public const GATEWAY_TYPE = 'config';


	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->method_title       = L10nHelper::__( 'Payment in installments and deferred with Alma' );
		$this->method_description = L10nHelper::__( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.' );
		$this->has_fields         = true;
		parent::__construct();

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
	 *
	 * @throws ContainerException
	 * @throws MerchantServiceException
	 */
	public function init_form_fields() {

		// Initialize minimum form fields
		$this->form_fields = array_merge(
			$this->form_fields,
			$this->enabled_field(),
			$this->api_key_fieldset(),
		);

		// If the plugin is configured, add the gateway and fee plan fields
		if ( Plugin::get_instance()->is_configured() ) {
			$this->form_fields = array_merge(
				$this->form_fields,
				$this->gateway_order_fieldset(),
				$this->fee_plan_fieldset(),
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
	 * @throws ContainerException
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
	 * @throws ContainerException
	 */
	public function sanitize_settings( array $settings ): array {

		return $this->encrypt_keys( $this->check_values( $settings ) );
	}

	/**
	 * Process admin Options and display errors
	 * @return bool
	 */
	public function process_admin_options(): bool {
		$saved = parent::process_admin_options();

		$this->display_errors();

		return $saved;
	}

	/**
	 * Check form values
	 *
	 * @param $settings
	 *
	 * @return array
	 * @throws ContainerException
	 */
	private function check_values( $settings ): array {
		$settings = $this->check_amounts( $settings );
		$settings = $this->check_keys( $settings );

		return $this->clean_decoration_fields( $settings );
	}

	/**
	 * Check if min_amount is less than max_amount.
	 *
	 * @param array $settings
	 *
	 * @return array
	 * @todo check if the given amount is between boundaries
	 */
	private function check_amounts( array $settings ): array {
		// Group pairs of min_amount and max_amount
		$groups = array();

		foreach ( $settings as $key => $value ) {
			$pattern = '/^(.*)_(' . self::MIN_AMOUNT_SUFFIX . '|' . self::MAX_AMOUNT_SUFFIX . ')$/';
			if ( preg_match( $pattern, $key, $matches ) ) {
				$groups[ $matches[1] ][ $matches[2] ] = $value;
			}
		}

		// Check each group for min_amount and max_amount
		foreach ( $groups as $prefix => $values ) {
			if ( empty( $values[ self::MIN_AMOUNT_SUFFIX ] ) && empty( $values[ self::MAX_AMOUNT_SUFFIX ] ) ) {
				unset( $settings[ $prefix . '_enabled' ] );
			} elseif ( $values[ self::MIN_AMOUNT_SUFFIX ] >= $values[ self::MAX_AMOUNT_SUFFIX ] ) {
				$this->add_error( "La valeur minimale de '$prefix' ne peut pas être supérieure ou égale à la valeur maximale." );
				unset( $settings[ $prefix . '_' . self::MIN_AMOUNT_SUFFIX ], $settings[ $prefix . '_' . self::MAX_AMOUNT_SUFFIX ] );
			}
		}

		return $settings;
	}

	/**
	 * Check if API keys are valid.
	 *
	 * @param array $settings
	 *
	 * @return array
	 * @throws ContainerException
	 */
	private function check_keys( array $settings ): array {
		/** @var AuthenticationService $authentication_service */
		$authentication_service = Plugin::get_container()->get( AuthenticationService::class );
		if ( ! $authentication_service->check_authentication(
			$settings[ self::FIELD_TEST_API_KEY ],
			ClientConfiguration::TEST_MODE
		) ) {
			unset( $settings[ self::FIELD_TEST_API_KEY ] );
			$this->add_error( 'La clé API de test n\'est pas valide.' );
		}
		if ( ! $authentication_service->check_authentication( $settings[ self::FIELD_LIVE_API_KEY ] ) ) {
			unset( $settings[ self::FIELD_LIVE_API_KEY ] );
			$this->add_error( 'La clé API de production n\'est pas valide.' );
		}

		return $settings;
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

	/**
	 * Encrypt keys.
	 *
	 * @param $settings array The whole post settings.
	 *
	 * @throws ContainerException
	 */
	private function encrypt_keys( array $settings ): array {

		/** @var EncryptorHelper $encryptor_helper */
		$encryptor_helper = Plugin::get_container()->get( EncryptorHelper::class );

		if ( ! empty( $settings[ self::FIELD_LIVE_API_KEY ] ) ) {
			$settings[ self::FIELD_LIVE_API_KEY ] = $encryptor_helper->encrypt( $settings[ self::FIELD_LIVE_API_KEY ] );
		}

		if ( ! empty( $settings[ self::FIELD_TEST_API_KEY ] ) ) {
			$settings[ self::FIELD_TEST_API_KEY ] = $encryptor_helper->encrypt( $settings[ self::FIELD_TEST_API_KEY ] );
		}

		return $settings;
	}
}
