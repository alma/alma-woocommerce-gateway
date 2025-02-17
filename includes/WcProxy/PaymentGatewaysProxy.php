<?php
/**
 * Payment gateways proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */

namespace Alma\Woocommerce\WcProxy;

class PaymentGatewaysProxy {

	private static $instance = null;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_payment_gateways() {
		return \WC_Payment_Gateways::instance()->payment_gateways();
	}
}
