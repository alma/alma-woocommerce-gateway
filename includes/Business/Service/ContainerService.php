<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\CurlClient;
use Alma\API\Endpoint\ConfigurationEndpoint;
use Alma\API\Endpoint\DataExportEndpoint;
use Alma\API\Endpoint\EligibilityEndpoint;
use Alma\API\Endpoint\MerchantEndpoint;
use Alma\API\Endpoint\OrderEndpoint;
use Alma\API\Endpoint\PaymentEndpoint;
use Alma\API\Endpoint\ShareOfCheckoutEndpoint;
use Alma\API\Endpoint\WebhookEndpoint;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Helper\GatewayFormHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\PluginHelper;
use Alma\Gateway\Business\Helper\RequirementsHelper;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\WooCommerce\Model\Gateway;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\OptionsProxy;
use Alma\Gateway\WooCommerce\Proxy\SettingsProxy;
use Dice\Dice;
use Psr\Http\Client\ClientInterface;

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
	 * @var OptionsService
	 */
	private OptionsService $options_service;

	/**
	 * ContainerService constructor.
	 * Init Rules
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
	public function get( $name, array $args = array(), array $share = array() ): object {
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
	 */
	private function set_business_rules() {
		// Business Layer
		$this->addRules(
			array(
				AdminService::class       => array( 'shared' => true ),
				OptionsService::class     => array( 'shared' => true ),
				SettingsService::class    => array( 'shared' => true ),
				WooCommerceService::class => array( 'shared' => true ),
				GatewayService::class     => array( 'shared' => true ),
				PaymentService::class     => array( 'shared' => true ),
			)
		);

		// API Layer
		$this->addRules(
			array(
				EligibilityService::class => array( 'shared' => true ),
			)
		);

		// PHP-Client
		if ( $this->options_service->is_configured() ) {
			$this->addRules(
				array(
					'Alma\API\ClientConfiguration' => array(
						'constructParams' => array(
							$this->options_service->get_active_api_key(),
							$this->options_service->get_environment(),
							array(),
						),
						'shared'          => true,
					),
					CurlClient::class              => array( 'shared' => true ),
				)
			);
		}

		// Endpoints
		$this->addRules(
			array(
				'*' => array(
					array(
						'substitutions' => array(
							ClientInterface::class => CurlClient::class,
						),
					),
				),
			)
		);

		$this->addRules(
			array(
				ConfigurationEndpoint::class   => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				DataExportEndpoint::class      => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				EligibilityEndpoint::class     => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				MerchantEndpoint::class        => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				OrderEndpoint::class           => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				PaymentEndpoint::class         => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				ShareOfCheckoutEndpoint::class => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
				WebhookEndpoint::class         => array(
					'substitutions' => array( ClientInterface::class => CurlClient::class ),
					'shared'        => true,
				),
			)
		);

		// Helpers
		$this->addRules(
			array(
				AssetsHelper::class       => array( 'shared' => true ),
				EncryptorHelper::class    => array( 'shared' => true ),
				GatewayFormHelper::class  => array( 'shared' => true ),
				L10nHelper::class         => array( 'shared' => true ),
				PluginHelper::class       => array( 'shared' => true ),
				RequirementsHelper::class => array( 'shared' => true ),
			)
		);
	}

	/**
	 * Set WooCommerce Layer Rules
	 */
	private function set_woocommerce_rules() {

		// WooCommerce Layer
		$this->addRules(
			array(
				HooksProxy::class    => array( 'shared' => true ),
				OptionsProxy::class  => array( 'shared' => true ),
				SettingsProxy::class => array( 'shared' => true ),
				Gateway::class       => array( 'shared' => true ),
			)
		);
	}
}
