<?php

namespace Alma\Gateway\Infrastructure\Block\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;

class GatewayBlockFactory {

	private ConfigService $config_service;

	public function __construct( ConfigService $config_service ) {
		$this->config_service = $config_service;
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
