<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Gateway\AbstractGatewayException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\FormHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Plugin;
use Alma\Plugin\Infrastructure\Helper\FormHelperInterface;
use WC_Order;

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

	public function __construct() {
		/** @var FormHelperInterface $form_helper */
		$form_helper       = Plugin::get_container()->get( FormHelper::class );
		$this->form_helper = $form_helper;
		parent::__construct();
		/** @var ConfigService $config_service */
		$config_service = Plugin::get_instance()->get_container()->get( ConfigService::class );
		$this->enabled  = $config_service->isEnabled() ? 'yes' : 'no';
	}

	public function setFormHelper( $formHelper ) {
		$this->form_helper = $formHelper;
	}

	public function setFeePlanRepository( $feePlanRepository ) {
		$this->fee_plan_repository = $feePlanRepository;
	}

	public function setConfigService( $configService ) {
		$this->config_service = $configService;
	}

	public function setCartAdapter( $cartAdapter ) {
		$this->cart_adapter = $cartAdapter;
	}

	public function setExcludedProductsHelper( $excludedProductsHelper ) {
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
		return sprintf( AbstractGateway::NAME_ALMA_GATEWAYS, $this->get_payment_method() );
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
	 * @throws FeePlanRepositoryException|AbstractGatewayException
	 */
	public function is_available(): bool {
		if ( ! parent::is_available() ) {
			return false;
		}

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
		/** @var ConfigService $config_service */
		$config_service      = Plugin::get_instance()->get_container()->get( ConfigService::class );
		$excluded_categories = $config_service->getExcludedCategories();

		$cart_adapter = ContextHelper::getCart();

		/** @var ExcludedProductsHelper $excluded_products_helper */
		$excluded_products_helper = Plugin::get_container()->get( ExcludedProductsHelper::class );
		if ( ! $excluded_products_helper->canDisplayOnCheckoutPage( $cart_adapter, $excluded_categories ) ) {
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
		/** @var FeePlanRepository $fee_plan_repository */
		$fee_plan_repository = Plugin::get_instance()->get_container()->get( FeePlanRepository::class );

		return $fee_plan_repository->getAllWithEligibility( ContextHelper::getCart()->getCartTotal() )->filterFeePlanList( array( $this->get_payment_method() ) )->filterEligible();
	}

	/**
	 * Get the transaction URL from the order meta.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return string|null The transaction URL.
	 */
	public function get_transaction_url( $order ) {
		return $order->get_meta( '_alma_payment_url' );
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
