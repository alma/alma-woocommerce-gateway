<?php

namespace Alma\Gateway\Business\Service;

use Dice\Dice;

class ContainerService extends Dice {

	/**
	 * ContainerService constructor.
	 * Init Rules
	 */
	public function __construct() {
		$this->set_business_rules();
		$this->set_woocommerce_rules();
	}

	/**
	 * Set Business Layer Rules
	 */
	private function set_business_rules() {
		// Business Layer
		$this->addRule( 'Alma\Gateway\Business\Service\AdminService', [
			'shared' => true,
		] );
		$this->addRule( 'Alma\Gateway\Business\Service\OptionsService', [
			'shared' => true,
		] );
		$this->addRule( 'Alma\Gateway\Business\Service\SettingsService', [
			'shared' => true,
		] );
		$this->addRule( 'Alma\Gateway\Business\Service\WooCommerceService', [
			'shared' => true,
		] );
		$this->addRule( 'Alma\Gateway\Business\Service\GatewayService', [
			'shared' => true,
		] );
		// Helpers
		$this->addRule( 'Alma\Gateway\Business\Helpers\EncryptorHelper', [
			'shared' => true,
		] );
	}

	/**
	 * Set WooCommerce Layer Rules
	 */
	private function set_woocommerce_rules() {
		// WooCommerce Layer
		$this->addRule( 'Alma\Gateway\WooCommerce\Proxy\HooksProxy', [
			'shared' => true,
		] );
		$this->addRule( 'Alma\Gateway\WooCommerce\Proxy\OptionsProxy', [
			'shared' => true,
		] );
		$this->addRule( 'Alma\Gateway\WooCommerce\Proxy\SettingsProxy', [
			'shared' => true,
		] );
	}

	/**
	 * Returns a fully constructed object based on $name using $args and $share as constructor arguments if supplied
	 *
	 * @param string $name The name of the class to instantiate
	 * @param array  $args An array with any additional arguments to be passed into the constructor upon instantiation
	 * @param array  $share Whether or not this class instance be shared, so that the same instance is passed around each time
	 *
	 * @return object A fully constructed object based on the specified input arguments
	 */
	public function get( $name, array $args = [], array $share = [] ) {
		error_reporting( error_reporting() & ~E_DEPRECATED );
		$service = $this->create( $name, $args = [], $share = [] );
		error_reporting( error_reporting() ^ E_DEPRECATED );

		return $service;
	}
}
