<?php

namespace Alma\Gateway\Application\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Exception\Service\InPageServiceException;
use Alma\Gateway\Infrastructure\Exception\Service\AssetsServiceException;
use Alma\Gateway\Infrastructure\Helper\CartHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;

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
	public function runInPage() {
		try {
			$this->assetsService->registerInPageAssets( [
				'environment' => $this->configService->getEnvironment()->getMode(),
				'merchant_id' => $this->configService->getMerchantId(),
				'number_decimals' => CartHelper::getCartPriceDecimalsNumber(),
			] );
		} catch ( AssetsServiceException $e ) {
			throw new InPageServiceException( 'Unable to load In-Page assets.', 0, $e );
		}

	}
}
