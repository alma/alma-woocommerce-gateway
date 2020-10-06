<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class Alma_WC_Settings {
	const OPTIONS_KEY = 'alma_wc_settings';
	const AMOUNT_KEYS = array( 'min_amount_2x', 'max_amount_2x', 'min_amount_3x', 'max_amount_3x', 'min_amount_4x', 'max_amount_4x' );

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

	public static function get_default_settings() {
		return array(
			'enabled'                               => 'yes',
			'enabled_2x'                            => 'no',
			'enabled_3x'                            => 'yes',
			'enabled_4x'                            => 'no',
			'title'                                 => __( 'Monthly Payments with Alma', 'alma-woocommerce-gateway' ),
			'description'                           => __( 'Pay in multiple monthly payments with your credit card.', 'alma-woocommerce-gateway' ),
			'display_cart_eligibility'              => 'yes',
			'display_product_eligibility'           => 'yes',
			'cart_is_eligible_message'              => __( 'Your cart is eligible for monthly payments', 'alma-woocommerce-gateway' ),
			'cart_not_eligible_message'             => __( 'Your cart is not eligible for monthly payments', 'alma-woocommerce-gateway' ),
			'variable_product_price_query_selector' => Alma_WC_Product_Handler::DEFAULT_VARIABLE_PRODUCT_PRICE_QUERY_SELECTOR,
			'excluded_products_list'                => array(),
			'cart_not_eligible_message_gift_cards'  => __( 'Gift cards cannot be paid with monthly installments', 'alma-woocommerce-gateway' ),
			'live_api_key'                          => '',
			'test_api_key'                          => '',
			'environment'                           => 'test',
			'debug'                                 => 'no',
		);
	}

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
		$settings                   = (array) get_option( self::OPTIONS_KEY, array() );
		$this->_settings            = array_merge( self::get_default_settings(), $settings );
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
	 * Is pnx enabled.
	 *
	 * @return bool
	 */
	public function is_pnx_enabled( $installments ) {
		return 'yes' === $this->__get( "enabled_${installments}x" );
	}

	/**
	 * Get enabled pnx plans list.
	 *
	 * @return array
	 */
	public function get_enabled_pnx_plans_list() {
		$pnx_list = array();

		foreach ( array( 2, 3, 4 ) as $installments ) {
			if ( $this->is_pnx_enabled( $installments ) ) {
				$pnx_list[] = array(
					'installments' => $installments,
					'min_amount'   => $this->get_min_amount( $installments ),
					'max_amount'   => $this->get_max_amount( $installments ),
				);
			}
		}

		return $pnx_list;
	}

	/**
	 * Get min amount for pnx.
	 *
	 * @param int $installments the number of installments.
	 *
	 * @return int
	 */
	public function get_min_amount( $installments ) {
		return $this->__get( "min_amount_${installments}x" );
	}

	/**
	 * Get max amount for pnx.
	 *
	 * @param int $installments the number of installments.
	 *
	 * @return int
	 */
	public function get_max_amount( $installments ) {
		return $this->__get( "max_amount_${installments}x" );
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

	/**
	 * @return bool
	 */
	public function is_live() {
		return $this->get_environment() === 'live';
	}

	/**
	 * @return bool
	 */
	public function is_test() {
		return $this->get_environment() === 'test';
	}
}
