<?php
/**
 * IntegrationConfigurationUrlService.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Services
 * @namespace Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Services;

use Alma\API\DependenciesError;
use Alma\API\Exceptions\AlmaException;
use Alma\API\ParamsError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;

/**
 * IntegrationConfigurationUrlService
 */
class IntegrationConfigurationUrlService {


	/**
	 * Alma Settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;

	/**
	 * Tool Helper.
	 *
	 * @var ToolsHelper
	 */
	protected $tool_helper;

	/**
	 * Alma Logger.
	 *
	 * @var AlmaLogger
	 */
	protected $alma_logger;

	/**
	 * IntegrationConfigurationUrlService constructor.
	 *
	 * @param AlmaSettings $alma_settings Alma Settings.
	 * @param ToolsHelper  $tool_helper Tool Helper.
	 * @param AlmaLogger   $alma_logger Alma Logger.
	 */
	public function __construct( $alma_settings, $tool_helper, $alma_logger ) {
		$this->alma_settings = $alma_settings;
		$this->tool_helper   = $tool_helper;
		$this->alma_logger   = $alma_logger;
	}

	/**
	 * Send collect config url to Alma
	 *
	 * @return void
	 */
	public function send() {
		try {
			$this->alma_settings->get_alma_client();
			$this->alma_settings->alma_client->configuration->sendIntegrationsConfigurationsUrl( $this->tool_helper->url_for_webhook( ConstantsHelper::COLLECT_URL ) );
		} catch ( AlmaException $e ) {
			$this->alma_logger->warning( 'Error while sending integrations configurations URL to Alma: ' . $e->getMessage() );
		} catch ( DependenciesError $e ) {
			$this->alma_logger->warning( $e->getMessage() );
		} catch ( ParamsError $e ) {
			$this->alma_logger->warning( $e->getMessage() );
		} catch ( \Alma\Woocommerce\Exceptions\AlmaException $e ) {
			$this->alma_logger->warning( $e->getMessage() );
		}
	}


}
