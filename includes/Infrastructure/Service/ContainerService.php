<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\Adapter\ProductAdapterInterface;
use Alma\API\Domain\Helper\ContextHelperInterface;
use Alma\API\Domain\Helper\EventHelperInterface;
use Alma\API\Domain\Helper\ExcludedProductsHelperInterface;
use Alma\API\Domain\Helper\FormHelperInterface;
use Alma\API\Domain\Helper\NavigationHelperInterface;
use Alma\API\Domain\Helper\NotificationHelperInterface;
use Alma\API\Domain\Helper\SecurityHelperInterface;
use Alma\API\Domain\Helper\SessionHelperInterface;
use Alma\API\Domain\Helper\WidgetHelperInterface;
use Alma\API\Domain\Repository\ConfigRepositoryInterface;
use Alma\API\Domain\Repository\GatewayRepositoryInterface;
use Alma\API\Domain\Repository\OrderRepositoryInterface;
use Alma\API\Domain\Repository\ProductCategoryRepositoryInterface;
use Alma\API\Domain\Repository\ProductRepositoryInterface;
use Alma\API\Infrastructure\ClientConfiguration;
use Alma\API\Infrastructure\CurlClient;
use Alma\API\Infrastructure\Endpoint\ConfigurationEndpoint;
use Alma\API\Infrastructure\Endpoint\DataExportEndpoint;
use Alma\API\Infrastructure\Endpoint\EligibilityEndpoint;
use Alma\API\Infrastructure\Endpoint\MerchantEndpoint;
use Alma\API\Infrastructure\Endpoint\OrderEndpoint;
use Alma\API\Infrastructure\Endpoint\PaymentEndpoint;
use Alma\API\Infrastructure\Endpoint\ShareOfCheckoutEndpoint;
use Alma\API\Infrastructure\Endpoint\WebhookEndpoint;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Helper\IpnHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Helper\RequirementsHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Provider\FeePlanProvider;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Service\AdminService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\GatewayService;
use Alma\Gateway\Application\Service\IpnService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Adapter\ProductAdapter;
use Alma\Gateway\Infrastructure\Gateway\Backend\AlmaGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\CoreHelper;
use Alma\Gateway\Infrastructure\Helper\EventHelper;
use Alma\Gateway\Infrastructure\Helper\FormHelper;
use Alma\Gateway\Infrastructure\Helper\NavigationHelper;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Infrastructure\Helper\SecurityHelper;
use Alma\Gateway\Infrastructure\Helper\SessionHelper;
use Alma\Gateway\Infrastructure\Helper\WidgetHelper;
use Alma\Gateway\Infrastructure\Repository\ConfigRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Infrastructure\Repository\ProductRepository;
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
class ContainerService {

	/** @var Dice */
	private Dice $dice;

	/**
	 * ContainerService constructor.
	 * Init Rules for the DI Container
	 */
	public function __construct() {
		$this->dice = new Dice();

		$this->setDiConfig();
		$this->setApplicationRules();
		$this->setInfrastructureRules();
		$this->setApiConfig();

		CoreHelper::autoReloadOptionsOnOptionSave();
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
	public function get( string $name, array $args = array(), array $share = array() ): object {

		// error_reporting( error_reporting() & ~E_DEPRECATED ); // phpcs:ignore
		// @formatter:off PHPStorm wants this call to be multiline
		$service = $this->dice->create( $name, $args, $share );
		// @formatter:on
		// error_reporting( error_reporting() ^ E_DEPRECATED ); // phpcs:ignore

		return $service;
	}

	/**
	 * Reload the DI container Options when the Options are updated.
	 */
	public function reloadOptions(): void {
		$this->setApiConfig();
	}

	public function setApiConfig() {

		/** @var ConfigService $configService Mandatory for API services */
		$configService = $this->get( ConfigService::class );
		$this->dice    = $this->dice->addRule(
			ClientConfiguration::class,
			array(
				'constructParams' => array(
					$configService->getActiveApiKey(),
					$configService->getEnvironment(),
				),
				'shared'          => true,
			)
		);

		$this->dice = $this->dice->addRule( CurlClient::class, array( 'shared' => true ) );

	}

	public function setDiConfig(): void {

		// Endpoints
		$this->dice = $this->dice->addRule(
			'*',
			array(
				'substitutions' => array(
					// Client
					ClientInterface::class                    => CurlClient::class,

					// Adapters
					CartAdapterInterface::class               => CartAdapter::class,
					OrderAdapterInterface::class              => OrderAdapter::class,
					ProductAdapterInterface::class            => ProductAdapter::class,

					// Helpers
					ContextHelperInterface::class             => ContextHelper::class,
					EventHelperInterface::class               => EventHelper::class,
					ExcludedProductsHelperInterface::class    => ExcludedProductsHelper::class,
					FormHelperInterface::class                => FormHelper::class,
					NavigationHelperInterface::class          => NavigationHelper::class,
					NotificationHelperInterface::class        => NotificationHelper::class,
					SecurityHelperInterface::class            => SecurityHelper::class,
					SessionHelperInterface::class             => SessionHelper::class,
					WidgetHelperInterface::class              => WidgetHelper::class,

					// Repositories
					ConfigRepositoryInterface::class          => ConfigRepository::class,
					GatewayRepositoryInterface::class         => GatewayRepository::class,
					OrderRepositoryInterface::class           => OrderRepository::class,
					ProductRepositoryInterface::class         => ProductRepository::class,
					ProductCategoryRepositoryInterface::class => ProductCategoryRepository::class,
				),
			),
		);
	}

	/**
	 * Set Application Layer Rules
	 */
	private function setApplicationRules(): void {
		// Business Layer
		$this->dice = $this->dice->addRules(
			array(
				AdminService::class   => array( 'shared' => true ),
				ConfigService::class  => array( 'shared' => true ),
				GatewayService::class => array( 'shared' => true ),
				LoggerService::class  => array( 'shared' => true ),
				IpnService::class     => array( 'shared' => true ),
			)
		);

		// API Layer
		$this->dice = $this->dice->addRules(
			array(
				EligibilityProvider::class => array( 'shared' => true ),
				FeePlanProvider::class     => array( 'shared' => true ),
				PaymentProvider::class     => array( 'shared' => true ),
			)
		);

		// Endpoints
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
				L10nHelper::class         => array( 'shared' => true ),
				PluginHelper::class       => array( 'shared' => true ),
				RequirementsHelper::class => array( 'shared' => true ),
				TemplateHelper::class     => array( 'shared' => true ),
				IpnHelper::class          => array( 'shared' => true ),
			)
		);
	}

	/**
	 * Set Infrastructure Layer Rules
	 */
	private function setInfrastructureRules() {

		// WooCommerce Layer
		$this->dice = $this->dice->addRules(
			array(
				AlmaGateway::class      => array( 'shared' => true ),
				ConfigRepository::class => array( 'shared' => true ),
				CreditGateway::class    => array( 'shared' => true ),
				EventHelper::class      => array( 'shared' => true ),
				PayLaterGateway::class  => array( 'shared' => true ),
				PayNowGateway::class    => array( 'shared' => true ),
				PnxGateway::class       => array( 'shared' => true ),
			)
		);
	}
}
