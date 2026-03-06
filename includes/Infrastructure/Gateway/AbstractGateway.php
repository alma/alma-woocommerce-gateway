<?php

namespace Alma\Gateway\Infrastructure\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Exception\Service\PaymentServiceException;
use Alma\Gateway\Application\Helper\DisplayHelper;
use Alma\Gateway\Application\Mapper\RefundMapper;
use Alma\Gateway\Application\Provider\PaymentProvider;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Application\Service\PaymentService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Exception\Gateway\GatewayException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Exception\Repository\OrderRepositoryException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\InPageHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\OrderRepository;
use Alma\Gateway\Infrastructure\Service\LoggerService;
use Alma\Gateway\Plugin;
use Psr\Log\LoggerInterface;
use WC_Payment_Gateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractGateway extends WC_Payment_Gateway {

	const NAME_ALMA_GATEWAYS = 'alma_%s_gateway';

	protected const PAYMENT_METHOD = 'abstract';

	protected const CACHE_ENABLED = false;

	protected bool $is_eligible = false;

	/** @var LoggerInterface */
	protected $logger_service;

	/** @var FeePlanRepository|object $fee_plan_repository */
	private FeePlanRepository $fee_plan_repository;

	/**
	 * Gateway constructor.
	 * This Gateway is used by WooCommerce, but no parameter is injected by WooCommerce,
	 * so we need to get all the dependencies by ourselves.
	 *
	 * All parameters are injected here are used for unit test
	 * Let the fallback to the container for production use
	 *
	 * @param FeePlanRepository|null $fee_plan_repository
	 * @param LoggerInterface|null   $logger_service
	 */
	public function __construct( ?FeePlanRepository $fee_plan_repository = null, ?LoggerInterface $logger_service = null ) {
		$this->fee_plan_repository = $fee_plan_repository ?? Plugin::get_container()->get( FeePlanRepository::class );
		$this->logger_service      = $logger_service ?? Plugin::get_container()->get( LoggerService::class );

		$this->id                 = sprintf( 'alma_%s_gateway', $this->get_payment_method() );
		$this->method_description = __(
			'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.',
			'alma-gateway-for-woocommerce'
		);
		$this->has_fields         = true;
		$this->supports           = array( 'products', 'refunds' );
		$this->icon               = $this->get_icon_url();

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
	 * @throws GatewayException
	 */
	public function get_fee_plan_list_adapter(): ?FeePlanListAdapter {

		/** @var FeePlanRepository $fee_plan_repository */
		$fee_plan_repository = Plugin::get_container()->get( FeePlanRepository::class );

		try {
			return $fee_plan_repository->getAll()->filterFeePlanList( array( $this->get_payment_method() ) );
		} catch ( FeePlanRepositoryException $e ) {
			throw new GatewayException( 'Can not get Fee Plans', 0, $e );
		}
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

		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		// Check Fee Plans availability
		try {
			$enabledFeePlans = $this->fee_plan_repository->getAll()->filterFeePlanList( array( $this->get_payment_method() ) )->filterEnabled();
		} catch ( FeePlanRepositoryException $e ) {
			$enabledFeePlans = new FeePlanListAdapter( new FeePlanList() );

			$this->logger_service->debug(
				'Error while fetching Fee Plans for gateway ' . $this->get_id(),
				array( 'exception' => $e )
			);
		}

		return count( $enabledFeePlans ) > 0;
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
	 * @throws GatewayException
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
		} catch ( OrderRepositoryException $e ) {
			throw new GatewayException( 'Can not process payment', 0, $e );
		}
		$fields = $this->process_payment_fields( $order );
		try {
			$fee_plan_adapter = $fee_plan_repository->getByPlanKey( $fields['alma_plan_key'] );
		} catch ( FeePlanRepositoryException $e ) {
			throw new GatewayException( 'Can not process payment', 0, $e );
		}
		$order->addOrderNote(
			sprintf(
			// translators: %s: Selected payment method.
				__( 'Selected payment method : %s', 'alma-gateway-for-woocommerce' ),
				$fee_plan_adapter->getLabel(),
			)
		);

		/** @var PaymentService $payment_service */
		$payment_service = Plugin::get_container()->get( PaymentService::class );
		try {
			$payment = $payment_service->createPayment(
				$order,
				$fee_plan_adapter
			);
		} catch ( PaymentServiceException $e ) {
			throw new GatewayException( 'Can not process payment', 0, $e );
		}

		// Update order status to pending
		$order->updateStatus( 'pending', __( 'Awaiting payment via Alma', 'alma-gateway-for-woocommerce' ) );
		$order->update_meta_data( '_alma_payment_id', $payment->getId() );
		$order->update_meta_data( '_alma_payment_url', $payment->getUrl() );

		/** @var BusinessEventsService $business_event_service */
		$business_event_service = Plugin::get_container()->get( BusinessEventsService::class );
		$business_event_service->saveAlmaPaymentId( $payment->getId() );

		$result = array();
		if ( $config_service->isInPageEnabled() ) {
			// In-page checkout with fallback redirection
			$result['alma_payment_id'] = $payment->getId();
			$result['result']          = 'success';
			$result['redirect']        = InPageHelper::getInPageRedirectionFallbackUrl(
				$payment->getId(),
				$fee_plan_adapter
			);
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
	 * @throws GatewayException
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		/** @var OrderRepository $order_repository */
		$order_repository = Plugin::get_container()->get( OrderRepository::class );
		try {
			$order = $order_repository->getById( $order_id );
		} catch ( OrderRepositoryException $e ) {
			throw new GatewayException( 'Can not process Refund', 0, $e );
		}

		/** @var PaymentProvider $payment_service */
		$payment_service = Plugin::get_container()->get( PaymentProvider::class );
		$response        = $payment_service->refundPayment(
			$order->getPaymentId(),
			( new RefundMapper() )->buildRefundDto(
				$order,
				$reason,
				DisplayHelper::price_to_cent( $amount )
			)
		);

		if ( ! $response ) {
			return __( 'Refund failed.', 'alma-gateway-for-woocommerce' );
		}

		// Add a note to the order
		$order_note = sprintf(
		// translators: %1$d: Amount refunded / %2$s: Refunded by.
			__( 'Order partially refunded (%1$d via Alma) by %2$s.', 'alma-gateway-for-woocommerce' ),
			$amount,
			wp_get_current_user()->display_name
		);
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
}
