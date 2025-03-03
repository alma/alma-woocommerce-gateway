<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\Business\Exception\ContainerException;
use Dice\Dice;

/**
 * This DI Container is a wrapper around Dice
 * It provides a way to define rules for the Dice container
 * and to get services from the container
 *
 * @see https://r.je/dice
 *
 * Class ContainerService
 * Dependency Injection Container
 */
class ContainerService extends Dice {
	/**
	 * @var object
	 */
	private $options_service;

	/**
	 * ContainerService constructor.
	 * Init Rules
	 * @throws ContainerException
	 */
	public function __construct() {
		/** @var OptionsService $options_service */
		$this->options_service = $this->get( OptionsService::class );

		$this->set_business_rules();
		$this->set_woocommerce_rules();
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
			throw new ContainerException( "Missing Service $name" );
		}

		return $service;
	}

	/**
	 * Set Business Layer Rules
	 * @throws ContainerException
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
		$this->addRule(
			'Alma\Gateway\Business\Service\PaymentService',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Service\EligibilityService',
			array( 'shared' => true )
		);

		// PHP-Client
		$this->addRule(
			'Alma\API\Client',
			array(
				'constructParams' => array(
					$this->options_service->get_active_api_key(),
					array( 'mode' => $this->options_service->get_environment() ),
				),
				'shared'          => true,
			)
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
		$this->addRule(
			'Alma\Gateway\Business\Helper\AssetsHelper',
			array( 'shared' => true )
		);
		$this->addRule(
			'Alma\Gateway\Business\Helper\PluginHelper',
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
}
