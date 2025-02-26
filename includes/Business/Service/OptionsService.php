<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Helpers\EncryptorHelper;
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
	 * @var string
	 */
	private $environment;
	/**
	 * @var string
	 */
	private $live_api_key;
	/**
	 * @var string
	 */
	private $test_api_key;

	/**
	 * @param EncryptorHelper $encryptor_helper
	 */
	public function __construct( EncryptorHelper $encryptor_helper ) {
		$this->encryptor_helper = $encryptor_helper;
	}

	/**
	 * Are we using test environment?
	 *
	 * @return bool
	 */
	public function is_test() {
		return OptionsProxy::get_environment() === self::ALMA_ENVIRONMENT_TEST;
	}

	/**
	 * Returns the active environment.
	 *
	 * @return string
	 */
	public function get_environment() {
		return self::ALMA_ENVIRONMENT_LIVE === OptionsProxy::get_environment()
			? self::ALMA_ENVIRONMENT_LIVE : self::ALMA_ENVIRONMENT_TEST;
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
		return OptionsProxy::get_environment() === self::ALMA_ENVIRONMENT_LIVE;
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
	 * @param $api_key
	 *
	 * @return void
	 */
	public function set_live_api_key( $api_key ) {
		$this->live_api_key = $this->encryptor_helper->encrypt( $api_key );
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
	 * @param $api_key
	 *
	 * @return void
	 */
	public function set_test_api_key( $api_key ) {
		$this->test_api_key = $this->encryptor_helper->encrypt( $api_key );
	}
}
