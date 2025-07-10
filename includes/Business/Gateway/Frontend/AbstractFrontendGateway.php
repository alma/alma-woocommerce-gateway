<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\API\Entities\FeePlanList;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Service\API\FeePlanService;
use Alma\Gateway\Plugin;
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

	/**
	 * Check if the gateway is enabled.
	 * @return bool
	 */
	public function is_enabled(): bool {
		// @todo do we want to enable the frontend gateways independently?
		// The parameter is actually disabled in the frontend gateways form.
		$enabled = $this->settings['enabled'] ?? 'no';

		return 'yes' === $enabled;
	}

	/**
	 * Check if the gateway is available.
	 * At this level we only check that the gateway has enabled Fee Plans.
	 * It calls the parent method to check the availability.
	 * @return bool
	 * @throws ContainerException|MerchantServiceException
	 */
	public function is_available(): bool {
		$available = false;
		foreach ( $this->get_fee_plan_list() as $fee_plan ) {
			if ( $fee_plan->isEnabled() ) {
				$available = true;
			}
		}

		// If no fee plan is enabled, the gateway is not available
		if ( ! $available ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Get the fee plan list for this gateway.
	 * This method retrieves the fee plans from the FeePlanService and filters them based on the gateway type.
	 * @return FeePlanList
	 * @throws MerchantServiceException|ContainerException
	 */
	public function get_fee_plan_list(): FeePlanList {
		/** @var FeePlanService $fee_plan_service */
		$fee_plan_service = Plugin::get_instance()->get_container()->get( FeePlanService::class );

		return $fee_plan_service->get_fee_plan_list()->filterFeePlanList( array( $this->get_type() ) );
	}
}
