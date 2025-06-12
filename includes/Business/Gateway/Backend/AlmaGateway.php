<?php

namespace Alma\Gateway\Business\Gateway\Backend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Helper\GatewayFormHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class AlmaGateway extends AbstractGateway {

	public const GATEWAY_TYPE = 'config';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->method_title = L10nHelper::__( 'Payment in installments and deferred with Alma - 2x 3x 4x' );
		parent::__construct();

		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Is gateway available?
	 * @return bool
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * @throws ContainerException
	 */
	public function init_form_fields() {
		/** @var GatewayFormHelper $gateway_form_helper */
		$gateway_form_helper = Plugin::get_container()->get( GatewayFormHelper::class );

		$this->form_fields = array_merge(
			$this->form_fields,
			$gateway_form_helper->enabled_field(),
			$gateway_form_helper->api_key_fieldset(),
			$gateway_form_helper->debug_fieldset(),
			$gateway_form_helper->l10n_fieldset()
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

		if ( ! empty( $this->settings[ GatewayFormHelper::FIELD_LIVE_API_KEY ] ) ) {
			$this->settings[ GatewayFormHelper::FIELD_LIVE_API_KEY ] = $encryptor_helper->decrypt( $this->settings[ GatewayFormHelper::FIELD_LIVE_API_KEY ] );
		}

		if ( ! empty( $this->settings[ GatewayFormHelper::FIELD_TEST_API_KEY ] ) ) {
			$this->settings[ GatewayFormHelper::FIELD_TEST_API_KEY ] = $encryptor_helper->decrypt( $this->settings[ GatewayFormHelper::FIELD_TEST_API_KEY ] );
		}
	}

	/**
	 * Sanitize our settings
	 * @throws ContainerException
	 */
	public function sanitize_settings( $settings ): array {
		return $this->encrypt_keys( $settings );
	}

	/**
	 * Encrypt keys.
	 *
	 * @param $post_data array The whole post data.
	 *
	 * @throws ContainerException
	 */
	private function encrypt_keys( array $post_data ): array {

		/** @var EncryptorHelper $encryptor_helper */
		$encryptor_helper = Plugin::get_container()->get( EncryptorHelper::class );

		if ( ! empty( $post_data[ GatewayFormHelper::FIELD_LIVE_API_KEY ] ) ) {
			$post_data[ GatewayFormHelper::FIELD_LIVE_API_KEY ] = $encryptor_helper->encrypt( $post_data[ GatewayFormHelper::FIELD_LIVE_API_KEY ] );
		}

		if ( ! empty( $post_data[ GatewayFormHelper::FIELD_TEST_API_KEY ] ) ) {
			$post_data[ GatewayFormHelper::FIELD_TEST_API_KEY ] = $encryptor_helper->encrypt( $post_data[ GatewayFormHelper::FIELD_TEST_API_KEY ] );
		}

		return $post_data;
	}
}
