<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

interface FrontendGatewayInterface {

	public function process_payment_fields( OrderAdapterInterface $order ): array;
}
