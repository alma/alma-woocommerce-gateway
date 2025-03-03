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
	private $encryptor_helper;

	/**
	 * @var OptionsProxy
	 */
	private $options_proxy;

	/**
	 * @param EncryptorHelper $encryptor_helper
	 * @param OptionsProxy    $options_proxy
	 */
	public function __construct( EncryptorHelper $encryptor_helper, OptionsProxy $options_proxy ) {
		$this->encryptor_helper = $encryptor_helper;
		$this->options_proxy    = $options_proxy;
	}

	/**
	 * Are we using test environment?
	 *
	 * @return bool
	 */
	public function is_test() {
		return $this->get_environment() === self::ALMA_ENVIRONMENT_TEST;
	}

	/**
	 * Returns the active environment.
	 *
	 * @return string
	 */
	public function get_environment() {
		return self::ALMA_ENVIRONMENT_LIVE === $this->get_options()['environment']
			? self::ALMA_ENVIRONMENT_LIVE : self::ALMA_ENVIRONMENT_TEST;
	}

	/**
	 * @return false|mixed|null
	 */
	public function get_options() {
		return $this->options_proxy->get_options();
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
	 * Are we using live environment?
	 *
	 * @return bool
	 */
	public function is_live() {
		return $this->get_environment() === self::ALMA_ENVIRONMENT_LIVE;
	}

	/**
	 * Gets API key for live environment.
	 *
	 * @return string
	 */
	public function get_live_api_key() {
		return $this->encryptor_helper->decrypt( $this->get_options()['live_api_key'] );
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string
	 */
	public function get_test_api_key() {
		return $this->encryptor_helper->decrypt( $this->get_options()['test_api_key'] );
	}

	/**
	 * Gets the debug mode
	 *
	 * @return bool
	 */
	public function is_debug() {
		return 'yes' === $this->get_options()['debug'];
	}
}
