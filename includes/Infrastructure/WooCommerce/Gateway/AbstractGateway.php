<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Gateway;

use Alma\API\DTO\RefundDto;
use Alma\API\Entity\EligibilityList;
use Alma\API\Entity\FeePlan;
use Alma\API\Entity\FeePlanList;
use Alma\API\Exception\Endpoint\PaymentEndpointException;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Exception\PaymentServiceException;
use Alma\Gateway\Application\Helper\AssetsHelper;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Mapper\CustomerMapper;
use Alma\Gateway\Application\Mapper\OrderMapper;
use Alma\Gateway\Application\Mapper\PaymentMapper;
use Alma\Gateway\Application\Service\API\FeePlanService;
use Alma\Gateway\Application\Service\API\PaymentService;
use Alma\Gateway\Application\Service\CacheService;
use Alma\Gateway\Infrastructure\WooCommerce\Exception\CoreException;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Repository\OrderRepository;
use Alma\Gateway\Plugin;
use WC_Payment_Gateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractGateway extends WC_Payment_Gateway {

	public const GATEWAY_TYPE = 'abstract';

	public const CACHE_ENABLED = false;

	/** @var string Identifier */
	public $id;

	/**
	 * @var ?EligibilityList $eligibility_list public only for debug in functions.php
	 * @todo Remove this public property when the eligibility and fee plans are fully implemented.
	 */
	public ?EligibilityList $eligibility_list = null;
	/**
	 * @var ?FeePlanList $fee_plan_list public only for debug in functions.php
	 * @todo Remove this public property when the eligibility and fee plans are fully implemented.
	 */
	public ?FeePlanList $fee_plan_list = null;
	protected bool $is_eligible        = false;

	/**
	 * Gateway constructor.
	 *
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->id                 = sprintf( 'alma_%s_gateway', $this->get_type() );
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
	 * Set the eligibility of the gateway based on the eligibility list.
	 *
	 * @param EligibilityList $eligibility_list The eligibility list to filter.
	 */
	public function configure_eligibility( EligibilityList $eligibility_list ): void {
		$this->eligibility_list = $eligibility_list->filterEligibilityList( $this->get_type() );
	}

	/**
	 * Set the max amount of the gateway based on the fee plans.
	 *
	 * @param FeePlanList $fee_plan_list The fee plan list to filter.
	 */
	public function configure_fee_plans( FeePlanList $fee_plan_list ): void {
		$this->fee_plan_list = $fee_plan_list->filterFeePlanList( array( $this->get_type() ) );
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 * @throws ContainerException
	 */
	public function get_icon_url(): string {

		/** @var AssetsHelper $asset_helper */
		$asset_helper = Plugin::get_container()->get( AssetsHelper::class );

		return $asset_helper->get_image( 'images/alma_logo.svg' );
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
	 * @throws ContainerException
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
	 * @param int $order_id The ID of the order to process.
	 *
	 * @return array|string[] The result of the payment processing.
	 *
	 * @throws ContainerException|CoreException
	 */
	public function process_payment( $order_id ): array {
		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		$order            = $order_repository->findById( $order_id );
		$fields           = $this->process_payment_fields( $order );
		$fee_plan         = $this->fee_plan_list->getByPlanKey( $fields['alma_plan_key'] );

		/** @var PaymentService $payment_service */
		$payment_service = Plugin::get_container()->get( PaymentService::class );
		try {
			$payment = $payment_service->create_payment(
				( new PaymentMapper() )->buildPaymentDto( $this, $order, $fee_plan ),
				( new OrderMapper() )->buildOrderDto( $order ),
				( new CustomerMapper() )->buildCustomerDto( $order ),
			);
		} catch ( PaymentServiceException $e ) {
			WordPressProxy::notify_error(
				L10nHelper::__( 'Une erreur est survenue lors de la création du paiement. Veuillez réessayer.' . $e->getMessage() ),
			);

			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}

		$order->update_status( 'pending', L10nHelper::__( 'En attente de paiement via Alma' ) );

		return array(
			'result'   => 'success',
			'redirect' => $payment->url,
		);
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
	 * @throws CoreException|ContainerException|ParametersException|PaymentEndpointException
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		$order            = $order_repository->findById( $order_id );

		/** @var PaymentService $payment_service */
		$payment_service = Plugin::get_container()->get( PaymentService::class );
		$response        = $payment_service->refund_payment(
			$order->getPaymentId(),
			( new RefundDto() )
				->setAmount( DisplayHelper::price_to_cent( $amount ) )
				->setMerchantReference( $order->getMerchantReference() )
				->setComment( $reason )
		);

		if ( ! $response ) {
			return WordPressProxy::error( 'refund error', L10nHelper::__( 'Refund failed.' ) );
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
		$order->add_order_note( $order_note );

		// WooCommerce will create WC_Order_Refund automatically
		return true;
	}

	/**
	 * Return the Origin of the payment.
	 *
	 * @return string
	 */
	public function get_origin(): string {
		return 'online';
	}

	/**
	 * Get the gateway identifier.
	 *
	 * @return string The identifier of the gateway.
	 */
	public function get_id(): string {
		return $this->id;
	}

	protected function get_type(): string {
		return static::GATEWAY_TYPE;
	}

	/**
	 * Check if one fee plan is eligible for the cart total.
	 * We compare the cart total with the fee plans' min and max amounts.
	 * The result can be cached to avoid multiple calls to the same cart total.
	 *
	 * @return bool
	 * @throws ContainerException
	 */
	private function is_cart_eligible(): bool {
		$eligibility = false;
		$total       = WooCommerceProxy::get_cart_total();

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
		// @todo Find a fix to avoid API calls here.
		if ( ! $this->fee_plan_list ) {
			$fee_plan_service    = Plugin::get_container()->get( FeePlanService::class );
			$this->fee_plan_list = $fee_plan_service->get_fee_plan_list();
		}

		// Check if at least one fee plan is eligible for the cart total amount for this gateway
		if ( isset( $this->fee_plan_list ) ) {
			/** @var FeePlan $fee_plan */
			foreach ( $this->fee_plan_list as $fee_plan ) {
				if ( $fee_plan->isEligible( $total ) ) {
					$eligibility = true;
				}
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
