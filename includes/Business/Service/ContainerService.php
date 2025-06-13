<?php

namespace Alma\Gateway\Business\Service;

use Alma\API\ClientConfiguration;
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
use Alma\Gateway\Business\Gateway\Backend\AlmaGateway;
use Alma\Gateway\Business\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Business\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Business\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Helper\GatewayFormHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\PluginHelper;
use Alma\Gateway\Business\Helper\RequirementsHelper;
use Alma\Gateway\Business\Service\API\EligibilityService;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
use Alma\Gateway\WooCommerce\Proxy\OptionsProxy;
use Dice\Dice;
use Exception;
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
class ContainerService {

	/** @var OptionsService */
	private OptionsService $options_service;

	/** @var Dice */
	private Dice $dice;

	/**
	 * ContainerService constructor.
	 * Init Rules
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->dice = new Dice();

		/** @var OptionsService $options_service Mandatory for API services */
		$options_service       = $this->get( OptionsService::class );
		$this->options_service = $options_service;

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
	public function get( string $name, array $args = array(), array $share = array() ): object {
		try {
			error_reporting( error_reporting() & ~E_DEPRECATED ); // phpcs:ignore
			// @formatter:off PHPStorm wants this call to be multiline
			$service = $this->dice->create( $name, $args, $share );
			// @formatter:on
			error_reporting( error_reporting() ^ E_DEPRECATED ); // phpcs:ignore
		} catch ( Exception $e ) {
			throw new ContainerException( "Missing Service $name" );
		}

		return $service;
	}

	/**
	 * Set Business Layer Rules
	 */
	private function set_business_rules(): void {
		// Business Layer
		$this->dice = $this->dice->addRules(
			array(
				AdminService::class       => array( 'shared' => true ),
				OptionsService::class     => array( 'shared' => true ),
				SettingsService::class    => array( 'shared' => true ),
				WooCommerceService::class => array( 'shared' => true ),
				GatewayService::class     => array( 'shared' => true ),
				LoggerService::class      => array( 'shared' => true ),
			)
		);

		// API Layer
		$this->dice = $this->dice->addRules(
			array(
				EligibilityService::class => array( 'shared' => true ),
				FeePlanService::class     => array( 'shared' => true ),
				PaymentService::class     => array( 'shared' => true ),
			)
		);

		// PHP-Client
		$this->dice = $this->dice->addRule(
			ClientConfiguration::class,
			array(
				'constructParams' => array(
					$this->options_service->get_active_api_key(),
					$this->options_service->get_environment(),
				),
				'shared'          => true,
			)
		);

		$this->dice = $this->dice->addRule(
			CurlClient::class,
			array( 'shared' => true )
		);

		// Endpoints
		$this->dice = $this->dice->addRule(
			'*',
			array(
				array(
					'substitutions' => array(
						ClientInterface::class => CurlClient::class,
					),

				),
			)
		);

		$this->dice = $this->dice->addRules(
			array(
				ConfigurationEndpoint::class   => array( 'shared' => true ),
				DataExportEndpoint::class      => array( 'shared' => true ),
				EligibilityEndpoint::class     => array( 'shared' => true ),
				MerchantEndpoint::class        => array( 'shared' => true ),
				OrderEndpoint::class           => array( 'shared' => true ),
				PaymentEndpoint::class         => array( 'shared' => true ),
				ShareOfCheckoutEndpoint::class => array( 'shared' => true ),
				WebhookEndpoint::class         => array( 'shared' => true ),
			)
		);

		// Helpers
		$this->dice = $this->dice->addRules(
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
		$this->dice = $this->dice->addRules(
			array(
				HooksProxy::class      => array( 'shared' => true ),
				OptionsProxy::class    => array( 'shared' => true ),
				SettingsProxy::class   => array( 'shared' => true ),
				AlmaGateway::class     => array( 'shared' => true ),
				CreditGateway::class   => array( 'shared' => true ),
				PayLaterGateway::class => array( 'shared' => true ),
				PayNowGateway::class   => array( 'shared' => true ),
				PnxGateway::class      => array( 'shared' => true ),
			)
		);
	}
}
