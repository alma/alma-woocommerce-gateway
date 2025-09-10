<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Adapter\OrderAdapterInterface;

interface FrontendGatewayInterface {

	public function process_payment_fields( OrderAdapterInterface $order ): array;
}
