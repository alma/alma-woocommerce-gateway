<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Application\Exception\Service\InPageServiceException;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Service\AssetsService;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class InPageService {

	/** @var ConfigService Service to manage options. */
	private ConfigService $configService;
	private AssetsService $assetsService;

	public function __construct(
		ConfigService $configService,
		AssetsService $assetsService
	) {
		$this->configService = $configService;
		$this->assetsService = $assetsService;
	}

	/**
	 * Display Alma In-Page script on the page.
	 *
	 * @return void
	 * @throws InPageServiceException
	 */
	public function displayInPage() {
		try {
			$this->assetsService->loadInPageAssets( [
				'environment' => $this->configService->getEnvironment()->getMode(),
				'merchant_id' => $this->configService->getMerchantId()
			] );
		} catch ( AssetsServiceException $e ) {
			throw new InPageServiceException( 'Unable to load In-Page assets.', 0, $e );
		}

	}
}
