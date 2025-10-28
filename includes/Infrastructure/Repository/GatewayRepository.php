<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\GatewayRepositoryInterface;
use Alma\Gateway\Infrastructure\Block\Gateway\CreditGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PayLaterGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PayNowGatewayBlock;
use Alma\Gateway\Infrastructure\Block\Gateway\PnxGatewayBlock;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Plugin;

class GatewayRepository implements GatewayRepositoryInterface {

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

	public function getAlmaGateways(): array {
		return $this->findAllAlmaGateways();
	}

	/**
	 * Get all Alma gateway blocks.
	 * @return array
	 */
	public function findAllAlmaGatewayBlocks(): array {

		/** @var PnxGatewayBlock $pnxCheckoutBlock */
		$pnxCheckoutBlock = Plugin::get_container()->get( PnxGatewayBlock::class );

		/** @var CreditGatewayBlock $creditCheckoutBlock */
		$creditCheckoutBlock = Plugin::get_container()->get( CreditGatewayBlock::class );

		/** @var PayLaterGatewayBlock $payLaterCheckoutBlock */
		$payLaterCheckoutBlock = Plugin::get_container()->get( PayLaterGatewayBlock::class );

		/** @var PayNowGatewayBlock $payNowCheckoutBlock */
		$payNowCheckoutBlock = Plugin::get_container()->get( PayNowGatewayBlock::class );

		return array(
			$pnxCheckoutBlock->get_name()      => $pnxCheckoutBlock,
			$creditCheckoutBlock->get_name()   => $creditCheckoutBlock,
			$payLaterCheckoutBlock->get_name() => $payLaterCheckoutBlock,
			$payNowCheckoutBlock->get_name()   => $payNowCheckoutBlock,
		);
	}
}
