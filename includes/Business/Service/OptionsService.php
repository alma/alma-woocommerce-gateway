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
	 * Check if the plugin is configured.
	 * @return bool
	 */
	public function is_configured(): bool {
		$is_configured = true;
		if ( empty( $this->get_options() ) ) {
			$is_configured = false;
		}
		if ( $this->get_active_api_key() === null || empty( $this->get_active_api_key() ) ) {
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
		if ( isset( $this->get_options()['environment'] ) ) {
			return self::ALMA_ENVIRONMENT_LIVE === $this->get_options()['environment']
				? self::ALMA_ENVIRONMENT_LIVE : self::ALMA_ENVIRONMENT_TEST;
		}

		return self::ALMA_ENVIRONMENT_LIVE;
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
	 * @return string|null
	 */
	public function get_active_api_key(): ?string {
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
	 * @return string|null
	 */
	public function get_live_api_key(): ?string {
		if ( isset( $this->get_options()['live_api_key'] ) ) {
			return $this->encryptor_helper->decrypt( $this->get_options()['live_api_key'] );
		}

		return null;
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string|null
	 */
	public function get_test_api_key(): ?string {
		if ( isset( $this->get_options()['test_api_key'] ) ) {
			return $this->encryptor_helper->decrypt( $this->get_options()['test_api_key'] );
		}

		return null;
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

	public function get_option( $key ): string {
		return $this->options_proxy->get_options()[ $key ] ?? '';
	}

	/**
	 * Toggle a Fee Plan status.
	 *
	 * @param string $fee_plan_key The ID of the Fee Plan to toggle.
	 *
	 * @return bool True if the Fee Plan is now enabled, false otherwise.
	 */
	public function toggle_fee_plan( string $fee_plan_key ): bool {
		$option                  = $fee_plan_key . '_enabled';
		$current_fee_plan_status = $this->get_option( $option );
		$new_fee_plan_status     = 'yes' === $current_fee_plan_status ? 'no' : 'yes';
		$this->options_proxy->update_option( $option, $new_fee_plan_status );

		return 'yes' === $new_fee_plan_status;
	}

	/**
	 * Check if a Fee Plan is enabled in the options.
	 *
	 * @param string $fee_plan_key
	 *
	 * @return bool
	 */
	public function is_fee_plan_enabled( string $fee_plan_key ): bool {
		$option = $fee_plan_key . '_enabled';

		return $this->get_option( $option ) === 'yes';
	}

	/**
	 * Get the maximum amount for a Fee Plan.
	 *
	 * @param string $fee_plan_key
	 *
	 * @return int
	 */
	public function get_max_amount( string $fee_plan_key ): int {
		return (int) $this->get_option( $fee_plan_key . '_max_amount' );
	}

	/**
	 * Get the minimum amount for a Fee Plan.
	 *
	 * @param string $fee_plan_key
	 *
	 * @return int
	 */
	public function get_min_amount( string $fee_plan_key ): int {
		return (int) $this->get_option( $fee_plan_key . '_min_amount' );
	}

	/**
	 * Get all options.
	 * @return array
	 * @todo make private, public only for debugging purposes
	 */
	public function get_options(): array {
		return $this->options_proxy->get_options();
	}

	public function delete_option( string $key ): bool {

		return $this->options_proxy->delete_option( $key );
	}
}
