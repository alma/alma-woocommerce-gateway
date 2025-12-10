<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Helper\FormHelperInterface;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
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
	private FeePlanRepository $fee_plan_repository;
	private ConfigService $config_service;
	private CartAdapter $cart_adapter;
	private ExcludedProductsHelper $excluded_products_helper;

	public function __construct(
		FormHelper $formHelper,
		FeePlanRepository $feePlanRepository,
		ConfigService $configService,
		CartAdapter $cartAdapter,
		ExcludedProductsHelper $excludedProductsHelper
	) {
		parent::__construct();
		$this->form_helper              = $formHelper;
		$this->fee_plan_repository      = $feePlanRepository;
		$this->config_service           = $configService;
		$this->cart_adapter             = $cartAdapter;
		$this->excluded_products_helper = $excludedProductsHelper;
	}

	/**
	 * Check if the gateway is a pay now gateway.
	 *
	 * @return bool
	 */
	public function is_pay_now(): bool {
		return false;
	}

	/**
	 * Check if the gateway is a pay later gateway.
	 *
	 * @return bool
	 */
	public function is_pay_later(): bool {
		return false;
	}

	/**
	 * Get the gateway name.
	 * The goal is to have a unique name for all frontend gateways
	 *
	 * @return string
	 */
	public function get_name(): string {
		return sprintf( 'alma_%s_gateway', $this->get_payment_method() );
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
	 * Check if the gateway is available.
	 * At this level we only check that the gateway has enabled Fee Plans.
	 * It calls the parent method to check the availability.
	 *
	 * @return bool
	 * @throws FeePlanRepositoryException
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
		$excluded_categories = $this->config_service->getExcludedCategories();

		if ( ! $this->excluded_products_helper->canDisplayOnCheckoutPage( $this->cart_adapter, $excluded_categories ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the fee plan list for this gateway.
	 * This method retrieves the fee plans from the FeePlanService and filters them based on the gateway type.
	 *
	 * @return FeePlanListAdapter
	 * @throws FeePlanRepositoryException
	 */
	public function getFeePlanList(): FeePlanListAdapter {
		var_dump( $this->fee_plan_repository->getAll() );
		return $this->fee_plan_repository->getAll();
		//      return $this->feePlanRepository->getAll()->filterFeePlanList( array( $this->get_payment_method() ) )->filterEnabled();
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
