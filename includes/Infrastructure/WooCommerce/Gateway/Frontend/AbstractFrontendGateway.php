<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Gateway\Frontend;

use Alma\API\Entity\FeePlanList;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Exception\MerchantServiceException;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\OptionsService;
use Alma\Gateway\Infrastructure\WooCommerce\Gateway\AbstractGateway;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractFrontendGateway extends AbstractGateway {

	private string $config_gateway_id = 'alma_config_gateway';

	/**
	 * Override the method to return the AlmaGateway option key for the frontend gateways.
	 * The goal is to share the same options for all gateways
	 *
	 * @return string
	 */
	public function get_option_key(): string {
		return $this->plugin_id . $this->config_gateway_id . '_settings';
	}

	/**
	 * Check if the gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$enabled = $this->settings['enabled'] ?? 'no';

		return 'yes' === $enabled;
	}

	/**
	 * Check if the gateway is available.
	 * At this level we only check that the gateway has enabled Fee Plans.
	 * It calls the parent method to check the availability.
	 *
	 * @return bool
	 * @throws ContainerException|MerchantServiceException
	 */
	public function is_available(): bool {

		// Check Fee Plans availability
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

		// Check if there are products in the cart that are in excluded categories.
		/** @var OptionsService $options_service */
		$options_service     = Plugin::get_instance()->get_container()->get( OptionsService::class );
		$excluded_categories = $options_service->get_excluded_categories();
		if ( ! ExcludedProductsHelper::can_display_on_checkout_page( $excluded_categories ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Get the fee plan list for this gateway.
	 * This method retrieves the fee plans from the FeePlanService and filters them based on the gateway type.
	 *
	 * @return FeePlanList
	 * @throws MerchantServiceException|ContainerException
	 */
	public function get_fee_plan_list(): FeePlanList {
		/** @var FeePlanService $fee_plan_service */
		$fee_plan_service = Plugin::get_instance()->get_container()->get( FeePlanService::class );

		return $fee_plan_service->get_fee_plan_list()->filterFeePlanList( array( $this->get_type() ) );
	}

	/**
	 * Check if params are valid.
	 *
	 * @param string $setting The setting to check.
	 * @param array  $expected_values The expected values for the settings.
	 *
	 * @return bool True if the settings are valid, false otherwise.
	 */
	protected function check_values( string $setting, array $expected_values ): bool {
		if ( empty( $setting ) || ! in_array( $setting, $expected_values, true ) ) {
			return false;
		}

		return true;
	}
}
