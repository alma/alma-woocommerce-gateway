<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\WooCommerce\Proxy\OptionsProxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OptionsService {

	const ALMA_ENVIRONMENT_LIVE = 'live';
	const ALMA_ENVIRONMENT_TEST = 'test';

	/**
	 * @var EncryptorHelper
	 */
	private EncryptorHelper $encryptor_helper;

	/**
	 * @var OptionsProxy
	 */
	private OptionsProxy $options_proxy;

	/**
	 * @param EncryptorHelper $encryptor_helper
	 * @param OptionsProxy    $options_proxy
	 */
	public function __construct( EncryptorHelper $encryptor_helper, OptionsProxy $options_proxy ) {
		$this->encryptor_helper = $encryptor_helper;
		$this->options_proxy    = $options_proxy;
	}


	/**
	 * @return bool
	 */
	public function is_configured(): bool {
		$is_configured = true;
		if ( empty( $this->get_options() ) ) {
			$is_configured = false;
		}
		if ( ! isset( $this->get_options()['environment'] ) ) {
			$is_configured = false;
		}
		if ( ! isset( $this->get_options()['live_api_key'] ) && ! isset( $this->get_options()['test_api_key'] ) ) {
			$is_configured = false;
		}

		return $is_configured;
	}

	/**
	 * Are we using test environment?
	 *
	 * @return bool
	 */
	public function is_test(): bool {
		return $this->get_environment() === self::ALMA_ENVIRONMENT_TEST;
	}

	/**
	 * Returns the active environment.
	 *
	 * @return string
	 */
	public function get_environment(): string {
		return self::ALMA_ENVIRONMENT_LIVE === $this->get_options()['environment']
			? self::ALMA_ENVIRONMENT_LIVE : self::ALMA_ENVIRONMENT_TEST;
	}

	/**
	 * Check if we have keys for the active environment.
	 *
	 * @return bool
	 */
	public function has_keys(): bool {
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
	public function get_active_api_key(): string {
		return $this->is_live() ? $this->get_live_api_key() : $this->get_test_api_key();
	}

	/**
	 * Are we using live environment?
	 *
	 * @return bool
	 */
	public function is_live(): bool {
		return $this->get_environment() === self::ALMA_ENVIRONMENT_LIVE;
	}

	/**
	 * Gets API key for live environment.
	 *
	 * @return string
	 */
	public function get_live_api_key(): string {
		return $this->encryptor_helper->decrypt( $this->get_options()['live_api_key'] );
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string
	 */
	public function get_test_api_key(): string {
		return $this->encryptor_helper->decrypt( $this->get_options()['test_api_key'] );
	}

	/**
	 * Gets the debug mode
	 *
	 * @return bool
	 */
	public function is_debug(): bool {
		if ( ! isset( $this->get_options()['debug'] ) ) {
			return false;
		}

		return 'yes' === $this->get_options()['debug'];
	}

	/**
	 * @return false|mixed|null
	 */
	private function get_options() {
		return $this->options_proxy->get_options();
	}
}
