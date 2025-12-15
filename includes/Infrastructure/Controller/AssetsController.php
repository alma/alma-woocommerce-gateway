<?php

namespace Alma\Gateway\Infrastructure\Controller;

use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\CheckoutServiceException;
use Alma\Gateway\Infrastructure\Exception\Controller\AssetsControllerException;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Alma\Gateway\Infrastructure\Service\CheckoutService;
use Alma\Gateway\Plugin;

class AssetsController {

	private AssetsService $assetsService;

	public function __construct(
		AssetsService $assetsService
	) {
		$this->assetsService = $assetsService;
	}

	public function run() {
		if ( ! Plugin::get_instance()->is_configured() ) {
			return;
		}

		if ( ContextHelper::isAdmin() || ( ContextHelper::isCheckoutPage() && ContextHelper::isCheckoutPageUseBlocks() ) ) {
			/** @var GatewayRepository $gatewayRepository */
			$gatewayRepository = Plugin::get_container()->get( GatewayRepository::class );
			$almaGatewayBlocks = $gatewayRepository->findAllAlmaGatewayBlocks();
			try {
				/** @var CheckoutService $checkoutService */
				$checkoutService        = Plugin::get_container()->get( CheckoutService::class );
				$params                 = $checkoutService->getCheckoutParams( $almaGatewayBlocks );
				$params['checkout_url'] = ContextHelper::getWebhookUrl( 'alma_checkout_data' );
				$this->assetsService->loadGatewayBlockAssets( $params );
			} catch ( CheckoutServiceException|AssetsServiceException $e ) {
				throw new AssetsControllerException( 'Unable to load block assets', 0, $e );
			}
		}
	}
}
