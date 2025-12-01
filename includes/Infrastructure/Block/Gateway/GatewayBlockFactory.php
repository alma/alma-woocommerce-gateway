<?php

namespace Alma\Gateway\Infrastructure\Block\Gateway;

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;
use Alma\Gateway\Infrastructure\Service\AssetsService;

class GatewayBlockFactory {

	private ConfigService $config_service;
	private AssetsService $assets_service;

	public function __construct( ConfigService $config_service, AssetsService $assets_service ) {
		$this->config_service = $config_service;
		$this->assets_service = $assets_service;
	}

	/**
	 * Create and prepare a checkout block.
	 *
	 * @throws CheckoutBlockException
	 */
	public function create_gateway_block( string $class_name ): AbstractGatewayBlock {

		if ( ! is_subclass_of( $class_name, AbstractGatewayBlock::class ) ) {
			throw new CheckoutBlockException(
				sprintf(
					'%s must be a subclass of %s',
					$class_name,
					AbstractGatewayBlock::class
				)
			);
		}

		return new $class_name(
			$this->config_service->isInPageEnabled(),
			'alma-gateway-block'
		);
	}
}
