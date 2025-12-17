<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\GatewayRepositoryInterface;
use Alma\Gateway\Infrastructure\Block\Gateway\CreditGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\GatewayBlockFactory;
use Alma\Gateway\Infrastructure\Block\Gateway\PayLaterGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PayNowGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PnxGatewayBlock;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\CreditGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayLaterGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PayNowGateway;
use Alma\Gateway\Infrastructure\Gateway\Frontend\PnxGateway;
use Alma\Gateway\Plugin;

class GatewayRepository implements GatewayRepositoryInterface {

	/**
	 * Ordered list of Alma gateways.
	 *
	 * The order is reversed compared to the display order on checkout page,
	 *
	 * @var array
	 */
	private array $gatewayOrderedList = [
		CreditGateway::class,
		PayLaterGateway::class,
		PnxGateway::class,
		PayNowGateway::class,
	];

	/**
	 * Ordered list of Alma gateway blocks.
	 *
	 * @var array
	 */
	private array $gatewayOrderedBlocksList = [
		PayNowGatewayBlock::class,
		PnxGatewayBlock::class,
		PayLaterGatewayBlock::class,
		CreditGatewayBlock::class,
	];

	/**
	 * Get all available Alma gateways
	 *
	 * @return array
	 */
	public function findAllAlmaGateways(): array {
		return $this->gatewayOrderedList;
	}

	/**
	 * Get all registered Alma gateways
	 *
	 * @return array
	 */
	public function findAllRegisteredAlmaGateways(): array {
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
		foreach ( $this->gatewayOrderedBlocksList as $gatewayBlock ) {
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
