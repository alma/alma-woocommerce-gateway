<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Gateway\Frontend;

use Alma\API\Domain\OrderInterface;

interface FrontendGatewayInterface {

	public function process_payment_fields( OrderInterface $order ): array;
}
