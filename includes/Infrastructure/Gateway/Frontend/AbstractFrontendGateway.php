<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Helper\FormHelperInterface;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Helper\FormHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractFrontendGateway extends AbstractGateway {

	/** @var FormHelper The form Adapter */
	protected FormHelperInterface $form_helper;
	private string $config_gateway_id = 'alma_config_gateway';

	public function __construct() {
		/** @var FormHelperInterface $form_helper */
		$form_helper       = Plugin::get_container()->get( FormHelper::class );
		$this->form_helper = $form_helper;

		parent::__construct();
	}

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
	 */
	public function is_available(): bool {

		// Check Fee Plans availability
		$available = false;
		foreach ( $this->getFeePlanList() as $fee_plan ) {
			if ( $fee_plan->isEnabled() ) {
				$available = true;
			}
		}

		// If no fee plan is enabled, the gateway is not available
		if ( ! $available ) {
			return false;
		}

		// Check if there are products in the cart that are in excluded categories.
		/** @var ConfigService $options_service */
		$options_service     = Plugin::get_instance()->get_container()->get( ConfigService::class );
		$excluded_categories = $options_service->getExcludedCategories();
		/** @var CartAdapter $cart_adapter */
		$cart_adapter = Plugin::get_container()->get( CartAdapter::class );
		/** @var ExcludedProductsHelper $form_helper */
		$excludedProductsHelper = Plugin::get_container()->get( ExcludedProductsHelper::class );
		if ( ! $excludedProductsHelper->canDisplayOnCheckoutPage( $cart_adapter, $excluded_categories ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Get the fee plan list for this gateway.
	 * This method retrieves the fee plans from the FeePlanService and filters them based on the gateway type.
	 *
	 * @return FeePlanListAdapter
	 */
	public function getFeePlanList(): FeePlanListAdapter {
		/** @var FeePlanRepository $fee_plan_repository */
		$fee_plan_repository = Plugin::get_instance()->get_container()->get( FeePlanRepository::class );

		return $fee_plan_repository->getAll()->filterFeePlanList( array( $this->get_type() ) );
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
