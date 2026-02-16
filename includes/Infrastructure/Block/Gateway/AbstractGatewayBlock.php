<?php
/**
 * AlmaBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks
 * @namespace Alma\Woocommerce\Blocks;
 */

namespace Alma\Gateway\Infrastructure\Block\Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Exception\Service\PaymentServiceException;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Application\Service\PaymentService;
use Alma\Gateway\Infrastructure\Adapter\OrderAdapter;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;
use Alma\Gateway\Infrastructure\Exception\Gateway\GatewayException;
use Alma\Gateway\Infrastructure\Gateway\Frontend\AbstractFrontendGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Service\LoggerServiceAwareTrait;
use Alma\Gateway\Plugin;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Exception;

/**
 * Alma_Blocks
 */
abstract class AbstractGatewayBlock extends AbstractPaymentMethodType {

	use LoggerServiceAwareTrait;

	/** @var AbstractFrontendGateway $gateway */
	protected AbstractFrontendGateway $gateway;

	/** @var bool $is_in_page_enabled */
	private bool $is_in_page_enabled;

	/** @var string $assets_handle */
	private string $assets_handle;

	public function __construct( bool $is_in_page_enabled, string $assets_handle ) {

		$this->is_in_page_enabled = $is_in_page_enabled;
		$this->assets_handle      = $assets_handle;
		$this->name               = $this->gateway->get_name() . '_block';
	}

	/**
	 * Initialize the block by adding necessary actions.
	 */
	public function initialize() {
		add_action(
			'woocommerce_rest_checkout_process_payment_with_context',
			array( $this, 'process_payment_with_context' ),
			10,
			2
		);
	}

	/**
	 * Returns the gateway associated with this block.
	 *
	 * @return AbstractFrontendGateway
	 */
	public function get_gateway(): AbstractFrontendGateway {
		return $this->gateway;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 * @throws CheckoutBlockException
	 */
	public function is_active(): bool {
		try {
			return ContextHelper::isCheckoutPageUseBlocks() && $this->gateway->is_enabled() && $this->gateway->is_available();
		} catch ( GatewayException $e ) {
			throw new CheckoutBlockException( 'Can not determine if Block is active or not', 0, $e );
		}
	}

	/**
	 * Send data to the js. Used by default by WooCommerce for static gateway data.
	 * To use it in the payment method script, use script like:
	 * const settings = window.wc.wcSettings.getSetting(`alma_paynow_gateway_block_data`, null)
	 * @return array
	 * @see src/alma-gateway-block/alma-gateway-block.js
	 */
	public function get_payment_method_data(): array {

		return array(
			'name'         => $this->get_name(),
			'title'        => $this->gateway->get_title(),
			'description'  => $this->gateway->get_description(),
			'gateway_name' => $this->gateway->get_name(),
			'label_button' => __( 'Pay With Alma', 'alma-gateway-for-woocommerce' ),
		);
	}

	/**
	 * Register the scripts to be loaded for the payment method.
	 * The handle returned here is used by WooCommerce to enqueue the script.
	 * Params are passed to the script.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles(): array {

		return array( $this->assets_handle );
	}

	/**
	 * Process the payment when this gateway is selected.
	 * use StoreApi to create the payment
	 *
	 * Non-Blocks payments use AbstractFrontendGateway::process_payment()
	 * @throws CheckoutBlockException
	 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection The parameter is passed by reference by WooCommerce
	 */
	public function process_payment_with_context( PaymentContext $context, PaymentResult &$result ) {
		// If the result is already set by another block, do nothing
		if ( $result->status ) {
			return;
		}

		if ( $context->payment_method === $this->gateway->get_name() ) {

			$order        = new OrderAdapter( $context->order );
			$payment_data = $context->payment_data;

			// Defensive: Ignore requests that do not have the required fields
			if (
				empty( $payment_data['alma_plan_key'] ) ||
				empty( $payment_data['alma_checkout_nonce_field'] )
			) {
				return;
			}

			try {
				$fee_plan_adapter = $this->gateway->get_fee_plan_list_adapter()->getByPlanKey( $payment_data['alma_plan_key'] );
			} catch ( GatewayException $e ) {
				throw new CheckoutBlockException( 'Can not process payment', 0, $e );
			}

			/** @var PaymentService $payment_service */
			$payment_service = Plugin::get_container()->get( PaymentService::class );
			try {
				$payment = $payment_service->createPayment(
					$order,
					$fee_plan_adapter
				);
			} catch ( PaymentServiceException $e ) {

				try {
					$result->set_status( 'error' );
				} catch ( Exception $e ) {
					throw new CheckoutBlockException( 'Can not process payment', 0, $e );
				}
				wc_add_notice(
					__( 'Payment processing failed. Please try again.', 'alma-gateway-for-woocommerce' ),
					'error'
				);

				$this->getLogger()->debug( 'Payment processing failed', array( 'exception' => $e ) );

				return;
			}

			$order->updateStatus( 'pending', __( 'Awaiting payment via Alma', 'alma-gateway-for-woocommerce' ) );
			$order->update_meta_data( '_alma_payment_id', $payment->getId() );
			$order->update_meta_data( '_alma_payment_url', $payment->getUrl() );
			$order->save();

			/** @var BusinessEventsService $business_event_service */
			$business_event_service = Plugin::get_container()->get( BusinessEventsService::class );
			$business_event_service->saveAlmaPaymentId( $payment->getId() );

			try {
				$result->set_status( 'success' );
			} catch ( Exception $e ) {
				throw new CheckoutBlockException( 'Can not process payment', 0, $e );
			}
			$result->set_payment_details(
				array(
					'alma_payment_id' => $payment->getId(),
					'alma_fee_plan'   => $payment_data['alma_plan_key'],
				)
			);

			// If it's redirect flow, set redirect URL
			if ( ! $this->is_in_page_enabled ) {
				$result->set_redirect_url( $payment->getUrl() );
			}
		}
	}
}
