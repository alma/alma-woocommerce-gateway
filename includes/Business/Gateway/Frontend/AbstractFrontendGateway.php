<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\WooCommerce\Gateway\AbstractGateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractFrontendGateway extends AbstractGateway {

	private string $config_gateway_id = 'alma_config_gateway';

	/**
	 * Override the method to return the AlmaGateway option key for the frontend gateways.
	 * The goal is to share the same options for all gateways
	 * @return string
	 */
	public function get_option_key(): string {
		return $this->plugin_id . $this->config_gateway_id . '_settings';
	}
}
