<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Exception\ContainerException;
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
		$this->addRule(
			'Alma\Gateway\Business\Service\AdminService',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Service\OptionsService',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Service\SettingsService',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Service\WooCommerceService',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Service\GatewayService',
			array( 'shared' => true )
		);
		// Helpers
		$this->addRule(
			'Alma\Gateway\Business\Helper\EncryptorHelper',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Helper\RequirementsHelper',
			array( 'shared' => true )
		);
	}

	/**
	 * Set WooCommerce Layer Rules
	 */
	private function set_woocommerce_rules() {
		// WooCommerce Layer
		$this->addRule(
			'Alma\Gateway\WooCommerce\Proxy\HooksProxy',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\WooCommerce\Proxy\OptionsProxy',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\WooCommerce\Proxy\SettingsProxy',
			array( 'shared' => true )
		);
	}

	/**
	 * Returns a fully constructed object based on $name using $args and $share as constructor arguments if supplied
	 *
	 * @param string $name The name of the class to instantiate
	 * @param array  $args An array with any additional arguments to be passed into the constructor upon instantiation
	 * @param array  $share Whether or not this class instance be shared, so that the same instance is passed around each time
	 *
	 * @return object A fully constructed object based on the specified input arguments
	 * @throws ContainerException
	 */
	public function get( $name, array $args = array(), array $share = array() ) {
		try {
			error_reporting( error_reporting() & ~E_DEPRECATED ); // phpcs:ignore
			// @formatter:off PHPStorm wants this call to be multiline
			$service = $this->create( $name, $args, $share );
			// @formatter:on
			error_reporting( error_reporting() ^ E_DEPRECATED ); // phpcs:ignore
		} catch ( \Exception $e ) {
			throw new ContainerException( 'Missing Service' );
		}

		return $service;
	}
}
