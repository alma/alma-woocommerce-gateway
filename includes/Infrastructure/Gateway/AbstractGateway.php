<?php

namespace Alma\Gateway\Infrastructure\Gateway;

use Alma\API\Application\DTO\RefundDto;
use Alma\API\Infrastructure\Exception\ParametersException;
use Alma\Gateway\Application\Exception\Service\API\PaymentServiceException;
use Alma\Gateway\Application\Exception\Service\GatewayServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Mapper\RefundMapper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\PaymentService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Repository\ProductRepositoryException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\InPageHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Service\CacheService;
use Alma\Gateway\Plugin;
use WC_Payment_Gateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractGateway extends WC_Payment_Gateway {

	protected const PAYMENT_METHOD = 'abstract';

	protected const CACHE_ENABLED = false;

	protected bool $is_eligible = false;

	/**
	 * Gateway constructor.
	 */
	public function __construct() {
		$this->id                 = sprintf( 'alma_%s_gateway', $this->get_payment_method() );
		$this->method_description = L10nHelper::__( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.' );
		$this->has_fields         = true;
		$this->supports           = array( 'products', 'refunds' );
		$this->init_form_fields();
		$this->init_settings();
		$this->icon = $this->get_icon_url();

		add_action(
			'woocommerce_update_options_payment_gateways_alma_config_gateway',
			array( $this, 'process_admin_options' ),
			1
		);
	}

	/**
	 * Get FeePlanListAdapter.
	 *
	 * @return FeePlanListAdapter|null
	 */
	public function get_fee_plan_list_adapter(): ?FeePlanListAdapter {

		$fee_plan_repository = Plugin::get_container()->get( FeePlanRepository::class );

		return $fee_plan_repository->getAll()->filterFeePlanList( array( $this->get_payment_method() ) );
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 */
	public function get_icon_url(): string {
		return AssetsHelper::getImage( 'images/alma_logo.svg' );
	}

	/**
	 * Is gateway enabled?
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === $this->enabled;
	}

	/**
	 * Is gateway available?
	 * At this level, we check:
	 * - if the cart total is within the max amount
	 * - if the gateway is enabled
	 * - if the gateway is eligible.
	 *
	 * @return bool
	 */
	public function is_available(): bool {

		// Is the gateway enabled?
		if ( ! $this->is_enabled() || ! $this->is_cart_eligible() ) {
			return false;
		}

		return true;
	}

	/**
	 * Process the payment for the order.
	 * This method is called when the customer clicks on the "Pay with Alma" button.
	 * It creates a payment with the Alma API and redirects the customer to the payment page.
	 *
	 * Blocks payments use AbstractGatewayBlock::process_payment_with_context()
	 *
	 * @param int $order_id The ID of the order to process.
	 *
	 * @return array|string[] The result of the payment processing.
	 *
	 * @throws GatewayServiceException
	 */
	public function process_payment( $order_id ): array {

		/** @var FeePlanRepository $fee_plan_repository */
		$fee_plan_repository = Plugin::get_container()->get( FeePlanRepository::class );
		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		/** @var ConfigService $config_service */
		$config_service = Plugin::get_container()->get( ConfigService::class );
		try {
			$order = $order_repository->getById( $order_id );
		} catch ( ProductRepositoryException $e ) {
			throw new GatewayServiceException( 'Can not find Order' );
		}
		$fields = $this->process_payment_fields( $order );
		try {
			$fee_plan_adapter = $fee_plan_repository->getByPlanKey( $fields['alma_plan_key'] );
		} catch ( FeePlanRepositoryException $e ) {
			throw new GatewayServiceException( 'Can not find Fee Plan' );
		}

		/** @var PaymentService $payment_service */
		$payment_service = Plugin::get_container()->get( PaymentService::class );
		try {
			$payment = $payment_service->createPayment(
				$order,
				$fee_plan_adapter
			);
		} catch ( PaymentServiceException $e ) {
			throw new GatewayServiceException( $e->getMessage() );
		}

		// Update order status to pending
		$order->updateStatus( 'pending', L10nHelper::__( 'En attente de paiement via Alma' ) );

		$result = array();
		if ( $config_service->isInPageEnabled() ) {
			// In-page checkout with fallback redirection
			$result['payment_id'] = $payment->getId();
			$result['result']     = 'success';
			$result['redirect']   = InPageHelper::getInPageRedirectionFallbackUrl( $payment->getId() );
		} else {
			// Classic checkout redirection
			$result['result']   = 'success';
			$result['redirect'] = $payment->getUrl();
		}

		return $result;
	}

	/**
	 * Process the Refund of an order.
	 * This method is called when a refund is requested from the WooCommerce admin.
	 *
	 * No need to check amount here, WooCommerce will handle it.
	 *
	 * @param int        $order_id The ID of the order to refund.
	 * @param float|null $amount The amount to refund. If null, the full order amount will be refunded.
	 * @param string     $reason The reason for the refund.
	 *
	 * @throws GatewayServiceException|ProductRepositoryException
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		$order            = $order_repository->getById( $order_id );

		/** @var PaymentProvider $payment_service */
		$payment_service = Plugin::get_container()->get( PaymentProvider::class );
		$response        = $payment_service->refundPayment(
			$order->getPaymentId(),
			( new RefundMapper() )->buildRefundDto(
				DisplayHelper::price_to_cent( $amount ),
				$reason,
				$order
			)
		);

		if ( ! $response ) {
			return L10nHelper::__( 'Refund failed.' );
		}

		// Add a note to the order
		if ( $order->isFullyRefunded() ) {
			/* translators: %s is a username. */
			$order_note = sprintf(
				L10nHelper::__( 'Order fully refunded by %s.' ),
				wp_get_current_user()->display_name
			);
		} else {
			/* translators: %s is a username. */
			$order_note = sprintf(
				L10nHelper::__( 'Order partially refunded (%d via Alma) by %s.' ),
				$amount,
				wp_get_current_user()->display_name
			);
		}
		$order->addOrderNote( $order_note );

		// WooCommerce will create WC_Order_Refund automatically
		return true;
	}

	/**
	 * Get the gateway identifier.
	 *
	 * @return string The identifier of the gateway.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get the Payment Method type of the gateway.
	 *
	 *
	 * @return string The Payment Method type of the gateway.
	 */
	public function get_payment_method(): string {
		return static::PAYMENT_METHOD;
	}

	/**
	 * Check if one fee plan is eligible for the cart total.
	 * We compare the cart total with the fee plans' min and max amounts.
	 * The result can be cached to avoid multiple calls to the same cart total.
	 *
	 * @return bool
	 * @todo move this to CartAdapter?
	 */
	private function is_cart_eligible(): bool {
		$eligibility = false;
		/** CartAdapterInterface $cart_adapter */
		$cart_adapter = Plugin::get_container()->get( CartAdapter::class );
		$total        = $cart_adapter->getCartTotal();

		// Is the availability already computed for this cart?
		$cache_service = Plugin::get_container()->get( CacheService::class );
		$cache_key     = sprintf( '%s_%f', $this->id, $total );
		if ( self::CACHE_ENABLED ) {
			$cached = $cache_service->get_cache( $cache_key );
			if ( null !== $cached ) {
				return (bool) $cached;
			}
		}

		// When running from AJAX, the fee plan list is not set.
		$fee_plan_repository   = Plugin::get_container()->get( FeePlanRepository::class );
		$fee_plan_list_adapter = $fee_plan_repository->getAll()->filterFeePlanList( array( $this->get_payment_method() ) );

		// Check if at least one fee plan is eligible for the cart total amount for this gateway
		/** @var FeePlanAdapter $fee_plan_adapter */
		foreach ( $fee_plan_list_adapter as $fee_plan_adapter ) {
			if ( $fee_plan_adapter->isEligible( $total ) ) {
				$eligibility = true;
			}
		}

		// Cache result
		if ( self::CACHE_ENABLED ) {
			// Cache and return the availability for this cart
			$cache_service->set_cache( $cache_key, $eligibility );
		}

		return $eligibility;
	}
}
