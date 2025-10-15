<?php

namespace Alma\Gateway\Application\Service;

use Alma\Gateway\Infrastructure\Helper\InPageHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class InPageService {

	/** @var ConfigService Service to manage options. */
	private ConfigService $configService;
	private InPageHelper $inPageHelper;

	public function __construct(
		ConfigService $configService,
		InPageHelper $inPageHelper
	) {
		$this->configService = $configService;
		$this->inPageHelper  = $inPageHelper;
	}

	/**
	 * Display Alma In-Page script on the page.
	 *
	 * @return void
	 */
	public function displayInPage() {
		$this->inPageHelper->displayInPageAssets(
			$this->configService->getMerchantId(),
			$this->configService->getEnvironment()
		);
	}
}
