<?php
/**
 * Payment gateways proxy.
 *
 * @package Alma\Woocommerce\WcProxy
 */
namespace Alma\Woocommerce\WcProxy;

use WC_Payment_Gateways;

class PaymentGatewaysProxy {

	/**
	 * @var PaymentGatewaysProxy|null
	 */
	private static $instance = null;

	/**
	 * @var WC_Payment_Gateways
	 */
	private $wc_payment_gateway;

	/**
	 * PaymentGatewaysProxy constructor.
	 *
	 * @param WC_Payment_Gateways|null $wc_payment_gateway
	 */
	private function __construct( $wc_payment_gateway = null) {
		$this->wc_payment_gateway = $wc_payment_gateway ? $wc_payment_gateway : WC_Payment_Gateways::instance();
	}

	/**
	 * Get the singleton instance of PaymentGatewaysProxy.
	 *
	 * @param WC_Payment_Gateways|null $wc_payment_gateway
	 * @return PaymentGatewaysProxy
	 */
	public static function get_instance( $wc_payment_gateway = null) {
		if (null === self::$instance) {
			self::$instance = new self( $wc_payment_gateway );
		}

		return self::$instance;
	}

	/**
	 * Get payment gateways.
	 *
	 * @return array
	 */
	public function get_payment_gateways() {
		return $this->wc_payment_gateway->payment_gateways();
	}

	/**
	 * Used for unit tests.
	 *
	 * @return void
	 */
	public static function reset_instance() {
		self::$instance = null;
	}

}
