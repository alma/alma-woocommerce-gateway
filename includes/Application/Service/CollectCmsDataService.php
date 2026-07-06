<?php

namespace Alma\Gateway\Application\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\DTO\MerchantData\CmsFeaturesDto;
use Alma\Client\Application\DTO\MerchantData\CmsInfoDto;
use Alma\Client\Application\DTO\MerchantData\MerchantDataDto;
use Alma\Client\Application\Endpoint\ConfigurationEndpoint;
use Alma\Client\Application\Exception\Endpoint\ConfigurationEndpointException;
use Alma\Client\Application\Helper\RequestHelper;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\AjaxHelper;
use Alma\Gateway\Infrastructure\Helper\CollectCmsDataHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Plugin;

class CollectCmsDataService {

	const COLLECT_DATA_URL_SENT_AT  = 'collect_data_url_sent_at';
	const URL_REFRESH_INTERVAL_DAYS = 30;
	const WC_API_ENDPOINT           = 'alma_collect_cms_data';

	private ConfigurationEndpoint $configurationEndpoint;
	private ConfigService $configService;
	private LoggerService $loggerService;
	private FeePlanRepository $feePlanRepository;
	private CollectCmsDataHelper $collectCmsDataHelper;

	public function __construct(
		ConfigurationEndpoint $configurationEndpoint,
		ConfigService $configService,
		LoggerService $loggerService,
		FeePlanRepository $feePlanRepository,
		CollectCmsDataHelper $collectCmsDataHelper
	) {
		$this->configurationEndpoint = $configurationEndpoint;
		$this->configService         = $configService;
		$this->loggerService         = $loggerService;
		$this->feePlanRepository     = $feePlanRepository;
		$this->collectCmsDataHelper  = $collectCmsDataHelper;
	}

	/**
	 * Send the CMS data collection URL to Alma if it has not been sent yet
	 * or if the last sent date is older than 30 days.
	 */
	public function sendCollectDataUrl(): void {
		if ( ! $this->shouldSendUrl() ) {
			return;
		}

		$url = add_query_arg(
			'wc-api',
			self::WC_API_ENDPOINT,
			home_url( '/' )
		);

		try {
			$this->configurationEndpoint->sendIntegrationsConfigurationsUrl( $url );
			$this->saveSentDate();
		} catch ( ConfigurationEndpointException $e ) {
			$this->loggerService->error(
				'Failed to send collect CMS data URL to Alma: ' . $e->getMessage(),
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Returns true if the URL should be sent to Alma:
	 * - the sent date setting is missing or invalid, or
	 * - the last sent date is older than 30 days.
	 */
	private function shouldSendUrl(): bool {
		$sentAt = $this->configService->getSetting( self::COLLECT_DATA_URL_SENT_AT );

		if ( empty( $sentAt ) ) {
			return true;
		}

		$sentTimestamp = strtotime( $sentAt );
		if ( $sentTimestamp === false ) {
			return true;
		}

		return ( time() - $sentTimestamp ) >= ( self::URL_REFRESH_INTERVAL_DAYS * DAY_IN_SECONDS );
	}

	/**
	 * Save the current date as the last sent date.
	 * Only called after a successful send — never on exception.
	 */
	private function saveSentDate(): void {
		$this->configService->createSetting( self::COLLECT_DATA_URL_SENT_AT, gmdate( 'c' ) );
	}

	/**
	 * Handle the CMS data collection request from Alma.
	 * Validates the HMAC signature before returning any data.
	 */
	public function handle(): void {
		if ( ! array_key_exists( 'HTTP_X_ALMA_SIGNATURE', $_SERVER ) ) {
			AjaxHelper::sendUnauthorizedResponse( 'Header key X-Alma-Signature does not exist.' );
			return;
		}

		$merchantId = $this->configService->getMerchantId();
		$apiKey     = $this->configService->getActiveApiKey();

		if ( empty( $merchantId ) || empty( $apiKey ) ) {
			AjaxHelper::sendUnauthorizedResponse( 'Unauthorized request.' );
			return;
		}

		if ( ! RequestHelper::isHmacValidated( $merchantId, $apiKey, $_SERVER['HTTP_X_ALMA_SIGNATURE'] ) ) {
			$this->loggerService->warning( 'Collect CMS data: signature validation failed.' );
			AjaxHelper::sendUnauthorizedResponse( 'Unauthorized request.' );
			return;
		}

		AjaxHelper::sendOkResponse(
			( new MerchantDataDto() )->toArray( $this->buildCmsInfo(), $this->buildCmsFeatures() )
		);
	}

	/**
	 * Build the CmsFeaturesDto with current CMS configuration data.
	 */
	private function buildCmsFeatures(): CmsFeaturesDto {
		$usedFeePlans = null;
		try {
			$usedFeePlans = $this->buildUsedFeePlans();
		} catch ( FeePlanRepositoryException $e ) {
			$this->loggerService->warning( 'Could not retrieve fee plans for CMS data: ' . $e->getMessage() );
		}

		return new CmsFeaturesDto(
			array(
				'alma_enabled'             => $this->configService->isEnabled(),
				'widget_cart_activated'    => $this->configService->getWidgetCartEnabled(),
				'widget_product_activated' => $this->configService->getWidgetProductEnabled(),
				'used_fee_plans'           => $usedFeePlans,
				'in_page_activated'        => $this->configService->isInPageEnabled(),
				'log_activated'            => $this->configService->isDebug(),
				'excluded_categories'      => $this->configService->getExcludedCategories(),
				'payment_method_position'  => $this->collectCmsDataHelper->getPaymentMethodPosition(),
				'specific_features'        => $this->collectCmsDataHelper->getSpecificFeatures(),
				'is_multisite'             => $this->collectCmsDataHelper->isMultisite(),
			)
		);
	}

	/**
	 * Build the used_fee_plans array from locally enabled fee plans.
	 *
	 * @return array|null Null if no plans are enabled.
	 * @throws FeePlanRepositoryException
	 */
	private function buildUsedFeePlans(): ?array {
		$plans    = array();
		$feePlans = $this->feePlanRepository->getAll()->getArrayCopy();

		foreach ( $feePlans as $feePlan ) {
			$planKey = $feePlan->getPlanKey();

			if ( ! $this->configService->isFeePlanEnabled( $planKey ) ) {
				continue;
			}

			$plans[ $planKey ] = array(
				'enabled'    => true,
				'min_amount' => $this->configService->getMinPurchaseAmount( $planKey ),
				'max_amount' => $this->configService->getMaxPurchaseAmount( $planKey ),
			);
		}

		ksort( $plans );

		return empty( $plans ) ? null : $plans;
	}

	/**
	 * Build the CmsInfoDto with current CMS and plugin metadata.
	 */
	private function buildCmsInfo(): CmsInfoDto {
		return new CmsInfoDto(
			array(
				'cms_name'              => 'WooCommerce',
				'cms_version'           => $this->collectCmsDataHelper->getCmsVersion(),
				'third_parties_plugins' => $this->collectCmsDataHelper->getThirdPartiesPlugins(),
				'theme_name'            => $this->collectCmsDataHelper->getThemeName(),
				'theme_version'         => $this->collectCmsDataHelper->getThemeVersion(),
				'language_name'         => 'PHP',
				'language_version'      => phpversion(),
				'alma_plugin_version'   => Plugin::ALMA_GATEWAY_PLUGIN_VERSION,
				'alma_sdk_version'      => $this->getAlmaSdkVersion(),
				'alma_sdk_name'         => 'alma/alma-php-client',
			)
		);
	}

	/**
	 * Returns the version of the Alma PHP client SDK from Composer's installed packages.
	 */
	private function getAlmaSdkVersion(): string {
		try {
			return \Composer\InstalledVersions::getPrettyVersion( 'alma/alma-php-client' ) ?? '';
		} catch ( \Throwable $e ) {
			return '';
		}
	}
}
