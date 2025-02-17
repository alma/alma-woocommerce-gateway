<?php
/**
 * Payment gateways proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */

namespace Alma\Woocommerce\WcProxy;

use WC_Payment_Gateways;

class PaymentGatewaysProxy {

	private static $instance = null;

	/**
	 * @var WC_Payment_Gateways
	 */
	private static $wc_payment_gateway;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function get_wc_payment_gateways() {
		if (null === self::$wc_payment_gateway) {
			self::$wc_payment_gateway = WC_Payment_Gateways::instance();
		}
		return self::$wc_payment_gateway;
	}

	public function get_payment_gateways() {
		return self::get_wc_payment_gateways()->payment_gateways();
	}

	/**
	 * Use for test
	 *
	 * @param WC_Payment_Gateways $wc_payment_gateway use only mock
	 *
	 * @return void
	 */
	public function set_wc_payment_gateways( $wc_payment_gateway) {
		self::$wc_payment_gateway = $wc_payment_gateway;
	}

}
