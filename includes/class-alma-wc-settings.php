<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class Alma_WC_Settings {
	const OPTIONS_KEY = 'alma_wc_settings';

	/**
	 * Setting values from get_option.
	 *
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * Flag to indicate setting has been loaded from DB.
	 *
	 * @var bool
	 */
	private $_are_settings_loaded = false;

	public function __set( $key, $value ) {
		$this->_settings[ $key ] = $value;
		$this->save();
	}

	public function __get( $key ) {
		if ( array_key_exists( $key, $this->_settings ) ) {
			return $this->_settings[ $key ];
		}

		return null;
	}

	public function __isset( $key ) {
		return array_key_exists( $key, $this->_settings );
	}

	public function __construct() {
		$this->load();
	}

	/**
	 * Load settings from DB.
	 *
	 * @param bool $force_reload Force reload settings
	 *
	 * @return Alma_WC_Settings Instance of Alma_Settings
	 */
	public function load( $force_reload = false ) {
		if ( $this->_are_settings_loaded && ! $force_reload ) {
			return $this;
		}
		$this->_settings            = (array) get_option( self::OPTIONS_KEY, array() );
		$this->_are_settings_loaded = true;

		return $this;
	}

	public function update_from( $settings = array() ) {
		foreach ( $settings as $key => $value ) {
			$this->_settings[ $key ] = $value;
		}

		$this->save();
	}

	public function save() {
		update_option( self::OPTIONS_KEY, $this->_settings );
	}

	/**
	 * Get API key for live environment.
	 *
	 * @return string
	 */
	public function get_live_api_key() {
		return $this->live_api_key;
	}

	/**
	 * Get API key for test environment.
	 *
	 * @return string
	 */
	public function get_test_api_key() {
		return $this->test_api_key;
	}

	/**
	 * Get API string for the current environment.
	 *
	 * @return string
	 */
	public function get_active_api_key() {
		return 'live' === $this->get_environment() ? $this->get_live_api_key() : $this->get_test_api_key();
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
	 * Is plugin "usable", i.e. is it enabled and correctly configured
	 *
	 * @return bool
	 */
	public function is_usable() {
		$user_cant_see = $this->get_environment() === 'test' && ! current_user_can( 'administrator' );

		return $this->is_enabled() && $this->fully_configured && ! $user_cant_see;
	}

	/**
	 * Is logging enabled.
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return 'yes' === $this->debug;
	}

	/**
	 * Get active environment from setting.
	 *
	 * @return string
	 */
	public function get_environment() {
		return 'live' === $this->environment ? 'live' : 'test';
	}
}
