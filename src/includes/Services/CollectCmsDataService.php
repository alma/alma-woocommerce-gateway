<?php
/**
 * CollectCmsDataService.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Services
 * @namespace Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Services;

use Alma\API\Client;
use Alma\API\DependenciesError;
use Alma\API\Entities\MerchantData\CmsFeatures;
use Alma\API\Entities\MerchantData\CmsInfo;
use Alma\API\Exceptions\AlmaException;
use Alma\API\Lib\PayloadFormatter;
use Alma\API\ParamsError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\SecurityHelperBuilder;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Exceptions\AlmaInvalidSignatureException;
use Alma\Woocommerce\Helpers\SecurityHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\WcProxy\FunctionsProxy;
use Alma\Woocommerce\WcProxy\OptionProxy;
use Alma\Woocommerce\WcProxy\ThemeProxy;

/**
 * CollectCmsDataService
 */
class CollectCmsDataService {

	const COLLECT_URL = 'alma_collect_data_url';

	/**
	 * Alma Settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;

	/**
	 * Security Helper.
	 *
	 * @var SecurityHelper
	 */
	protected $security_helper;

	/**
	 * Alma Logger.
	 *
	 * @var AlmaLogger
	 */
	protected $alma_logger;

	/**
	 * Payload Formatter.
	 *
	 * @var PayloadFormatter
	 */
	protected $payload_formatter;

	/**
	 * Option Proxy.
	 *
	 * @var OptionProxy
	 */
	protected $option_proxy;

	/**
	 * Theme Proxy.
	 *
	 * @var ThemeProxy
	 */
	protected $theme_proxy;

	/**
	 * Functions Proxy.
	 *
	 * @var FunctionsProxy
	 */
	protected $functions_proxy;

	/**
	 * Tool Helper.
	 *
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	/**
	 * CollectCmsDataService constructor.
	 *
	 * @param AlmaSettings     $alma_settings Alma Settings.
	 * @param AlmaLogger       $alma_logger Alma Logger.
	 * @param PayloadFormatter $payload_formatter Payload Formatter.
	 * @param SecurityHelper   $security_helper Security Helper.
	 * @param OptionProxy      $option_proxy Option Proxy.
	 * @param ThemeProxy       $theme_proxy Theme Proxy.
	 * @param FunctionsProxy   $functions_proxy Functions Proxy.
	 * @param ToolsHelper      $tools_helper Tools Helper.
	 */
	public function __construct(
		$alma_settings = null,
		$alma_logger = null,
		$payload_formatter = null,
		$security_helper = null,
		$option_proxy = null,
		$theme_proxy = null,
		$functions_proxy = null,
		$tools_helper = null
	) {
		$this->alma_settings     = isset( $alma_settings ) ? $alma_settings : new AlmaSettings();
		$this->alma_logger       = isset( $alma_logger ) ? $alma_logger : new AlmaLogger();
		$this->payload_formatter = isset( $payload_formatter ) ? $payload_formatter : new PayloadFormatter();
		$this->security_helper   = isset( $security_helper ) ? $security_helper : ( new SecurityHelperBuilder() )->get_instance();
		$this->option_proxy      = isset( $option_proxy ) ? $option_proxy : new OptionProxy();
		$this->theme_proxy       = isset( $theme_proxy ) ? $theme_proxy : new ThemeProxy();
		$this->functions_proxy   = isset( $functions_proxy ) ? $functions_proxy : new FunctionsProxy();
		$this->tools_helper      = isset( $tools_helper ) ? $tools_helper : ( new ToolsHelperBuilder() )->get_instance();
	}

	/**
	 * Send collect config url to Alma
	 *
	 * @return void
	 */
	public function send_url() {
		try {
			$this->alma_settings->get_alma_client();
			$this->alma_settings->alma_client->configuration->sendIntegrationsConfigurationsUrl( $this->tools_helper->url_for_webhook( self::COLLECT_URL ) );
		} catch ( AlmaException $e ) {
			$this->alma_logger->warning( 'Error while sending integrations configurations URL to Alma: ' . $e->getMessage() );
		} catch ( DependenciesError $e ) {
			$this->alma_logger->warning( 'Dependencies error: ' . $e->getMessage() );
		} catch ( ParamsError $e ) {
			$this->alma_logger->warning( 'Params Error: ' . $e->getMessage() );
		} catch ( \Alma\Woocommerce\Exceptions\AlmaException $e ) {
			$this->alma_logger->warning( 'Alma exception: ' . $e->getMessage() );
		}
	}

	/**
	 * Handle collect cms data
	 *
	 * @return void
	 */
	public function handle_collect_cms_data() {
		if ( ! array_key_exists( 'HTTP_X_ALMA_SIGNATURE', $_SERVER ) ) {
			$this->alma_logger->error( 'Header key X-Alma-Signature doesn\'t exist' );
			$this->functions_proxy->send_http_response( array( 'error' => 'Header key X-Alma-Signature doesn\'t exist' ), 403 );
			return;
		}

		try {
			$this->security_helper->validate_collect_data_signature(
				$this->alma_settings->get_active_merchant_id(),
				$this->alma_settings->get_active_api_key(),
				$_SERVER['HTTP_X_ALMA_SIGNATURE']
			);
			$this->functions_proxy->send_http_response( $this->payload_formatter->formatConfigurationPayload( $this->get_cms_info(), $this->get_cms_features() ), 200 );
		} catch ( AlmaInvalidSignatureException $e ) {
			$this->alma_logger->error( $e->getMessage() );
			$this->functions_proxy->send_http_response( array( 'error' => $e->getMessage() ), 403 );
		}
	}

	/**
	 * Get CMS features.
	 *
	 * @return CmsFeatures
	 */
	private function get_cms_features() {
		return new CmsFeatures(
			array(
				'alma_enabled'             => $this->alma_settings->is_enabled(),
				'widget_cart_activated'    => 'yes' === $this->alma_settings->display_cart_eligibility,
				'widget_product_activated' => 'yes' === $this->alma_settings->display_product_eligibility,
				'used_fee_plans'           => $this->format_fee_plans(),
				'in_page_activated'        => $this->alma_settings->display_in_page,
				'log_activated'            => 'yes' === $this->alma_settings->debug,
				'excluded_categories'      => $this->alma_settings->excluded_products_list,
				'is_multisite'             => is_multisite(),
			)
		);
	}

	/**
	 * Get CMS info.
	 *
	 * @return CmsInfo
	 */
	private function get_cms_info() {
		return new CmsInfo(
			array(
				'cms_name'              => 'WooCommerce',
				'cms_version'           => $this->option_proxy->get_option( 'woocommerce_version' ),
				'third_parties_plugins' => $this->format_third_party_modules(),
				'theme_name'            => $this->theme_proxy->get_name(),
				'theme_version'         => $this->theme_proxy->get_version(),
				'language_name'         => 'PHP',
				'language_version'      => phpversion(),
				'alma_plugin_version'   => ALMA_VERSION,
				'alma_sdk_version'      => Client::VERSION,
				'alma_sdk_name'         => 'alma/alma-php-client',
			)
		);
	}

	/**
	 * Format third party modules.
	 *
	 * @return array
	 */
	private function format_third_party_modules() {
		$active_plugins      = $this->option_proxy->get_option( 'active_plugins' );
		$third_party_modules = array();
		foreach ( $active_plugins as $plugin_data ) {
			$third_party_modules[] = array( 'name' => $plugin_data );
		}

		return $third_party_modules;
	}

	/**
	 * Format fee plans.
	 *
	 * @return array
	 */
	private function format_fee_plans() {
		$plans = array();

		if ( empty( $this->alma_settings->allowed_fee_plans ) ) {
			return $plans;
		}

		foreach ( $this->alma_settings->allowed_fee_plans as $fee_plan ) {
			$plan_key = $fee_plan->getPlanKey();

			$plans[ $plan_key ] = array(
				'enabled'    => $this->alma_settings->is_plan_enabled( $plan_key ),
				'min_amount' => $this->alma_settings->get_min_amount( $plan_key ),
				'max_amount' => $this->alma_settings->get_max_amount( $plan_key ),
			);
		}

		uksort( $plans, array( $this->alma_settings->fee_plan_helper, 'alma_usort_plans_keys' ) );

		return $plans;
	}

}
