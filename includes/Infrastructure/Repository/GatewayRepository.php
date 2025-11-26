<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\GatewayRepositoryInterface;
use Alma\Gateway\Infrastructure\Block\Gateway\CheckoutBlockFactory;
use Alma\Gateway\Infrastructure\Block\Gateway\CreditGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\GatewayBlockFactory;
use Alma\Gateway\Infrastructure\Block\Gateway\PayLaterGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PayNowGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PnxGatewayBlock;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Plugin;

class GatewayRepository implements GatewayRepositoryInterface {

	private array $gatewayBlocks = [
		PnxGatewayBlock::class,
		CreditGatewayBlock::class,
		PayLaterGatewayBlock::class,
		PayNowGatewayBlock::class
	];

	/**
	 * Get all Alma gateways.
	 *
	 * @return array
	 */
	public function findAllAlmaGateways(): array {
		return array_filter(
			WC()->payment_gateways()->payment_gateways(),
			function ( $gateway ) {
				return $gateway instanceof AbstractGateway;
			}
		);
	}

	/**
	 * Get all Alma gateway blocks.
	 * @return array
	 */
	public function findAllAlmaGatewayBlocks(): array {

		/** @var GatewayBlockFactory $gatewayBlockFactory */
		$gatewayBlockFactory = Plugin::get_container()->get( GatewayBlockFactory::class );

		$blocks = array();
		foreach ( $this->gatewayBlocks as $gatewayBlock ) {
			try {
				$block                        = $gatewayBlockFactory->create_gateway_block( $gatewayBlock );
				$blocks[ $block->get_name() ] = $block;
			} catch ( CheckoutBlockException $e ) {
				// If any block cannot be created, skip it.
				continue;
			}
		}

		return $blocks;
	}
}
