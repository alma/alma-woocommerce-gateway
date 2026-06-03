<?php

namespace Alma\Gateway\Application\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Application\Endpoint\ConfigurationEndpoint;
use Alma\Client\Application\Exception\Endpoint\ConfigurationEndpointException;
use Alma\Gateway\Infrastructure\Service\LoggerService;

class CollectCmsDataService {

	const COLLECT_DATA_URL_SENT_AT  = 'collect_data_url_sent_at';
	const URL_REFRESH_INTERVAL_DAYS = 30;
	const COLLECT_CMS_DATA_REST_NAMESPACE = 'alma/v1';
	const COLLECT_CMS_DATA_REST_ROUTE     = '/collect-cms-data';

	private ConfigurationEndpoint $configurationEndpoint;
	private ConfigService $configService;
	private LoggerService $loggerService;

	public function __construct(
		ConfigurationEndpoint $configurationEndpoint,
		ConfigService $configService,
		LoggerService $loggerService
	) {
		$this->configurationEndpoint = $configurationEndpoint;
		$this->configService         = $configService;
		$this->loggerService         = $loggerService;
	}

	/**
	 * Send the CMS data collection URL to Alma if it has not been sent yet
	 * or if the last sent date is older than 30 days.
	 */
	public function sendCollectDataUrl(): void {
		if ( ! $this->shouldSendUrl() ) {
			return;
		}

		$url = rest_url( self::COLLECT_CMS_DATA_REST_NAMESPACE . self::COLLECT_CMS_DATA_REST_ROUTE );

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
}
