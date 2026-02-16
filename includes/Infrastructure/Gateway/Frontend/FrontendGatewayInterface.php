<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

interface FrontendGatewayInterface {

	public function process_payment_fields( OrderAdapterInterface $order ): array;
}
