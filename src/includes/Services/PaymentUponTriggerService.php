<?php
/**
 * PaymentUponTriggerService.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Services
 * @namespace Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Services;

use Alma\API\Entities\FeePlan;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * PaymentUponTriggerService
 */
class PaymentUponTriggerService {


	/**
	 * Order Statuses without "wc-" prefix
	 *
	 * @var array
	 */
	private static $order_statuses = array();
	/**
	 * The db settings
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;
	/**
	 * Logger
	 *
	 * @var AlmaLogger
	 */
	private $logger;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger        = new AlmaLogger();
		$this->alma_settings = new AlmaSettings();
	}

	/**
	 * Callback function for the event "order status changed".
	 *
	 * @param integer $order_id The order id.
	 * @param string  $previous_status Order status before it changes.
	 * @param string  $next_status Order status affected to the order.
	 * @return void
	 */
	public function woocommerce_order_status_changed( $order_id, $previous_status, $next_status ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$wc_order = wc_get_order( $order_id );
		if ( 'alma' !== $wc_order->get_payment_method() ) {
			return;
		}

		if ( ! $wc_order->get_meta( 'alma_payment_upon_trigger_enabled' ) ) {
			return;
		}

		if ( $this->alma_settings->payment_upon_trigger_event === $next_status ) {
			$this->trigger_payment( $order_id, $next_status );
		}
	}

	/**
	 * Launches the payment on trigger for an order.
	 *
	 * @param integer $order_id The order id.
	 * @param string  $next_status The order new status.
	 *
	 * @return void
	 */
	protected function trigger_payment( $order_id, $next_status ) {
		$wc_order = wc_get_order( $order_id );

		if ( ! $wc_order->get_transaction_id() ) {
			$this->logger->error(
				'Error while getting transaction_id on trigger_payment for the order.',
				array(
					'Method'  => __METHOD__,
					'OrderId' => $order_id,
				)
			);

			return;
		}

		try {
			$payment = $this->alma_settings->fetch_payment( $wc_order->get_transaction_id() );
		} catch ( \Exception $e ) {
			$this->logger->error(
				'Fail to fetch payment with transaction_id for the order.',
				array(
					'Method'           => __METHOD__,
					'OrderId'          => $order_id,
					'TransactionId'    => $wc_order->get_transaction_id(),
					'ExceptionMessage' => $e->getMessage(),
				)
			);

			return;
		}

		if ( $payment->deferred_trigger_applied ) {
			$this->logger->warning(
				'This order was already triggered for payments.',
				array(
					'Method'    => __METHOD__,
					'OrderId'   => $order_id,
					'Triggered' => $payment->deferred_trigger_applied,
				)
			);
		} else {
			try {
				$this->alma_settings->trigger_payment( $wc_order->get_transaction_id() );
				// translators: %s: An order status (example: "completed").
				$wc_order->add_order_note( sprintf( __( 'The first customer payment has been triggered, as you updated the order status to "%s".', 'alma-gateway-for-woocommerce' ), $next_status ) );
			} catch ( \Exception $e ) {
				$this->logger->log_stack_trace(
					'Error while trigger payment for order number.',
					$e,
					array(
						'OrderId' => $order_id,
						'Method'  => __METHOD__,
					)
				);
			}
		}
	}

	/**
	 * Returns the lists of existing order statuses, without "wc-" prefix because the WC hook "woocommerce_order_status_changed" passes the order status without prefix.
	 *
	 * @return array
	 */
	public function get_order_statuses() {
		if ( empty( self::$order_statuses ) ) {
			foreach ( wc_get_order_statuses() as $status_key => $status_description ) {
				self::$order_statuses[ str_replace( 'wc-', '', $status_key ) ] = $status_description;
			}
		}
		return self::$order_statuses;
	}

	/**
	 * Has the merchant the "payment upon trigger" enabled in his admin alma dashboard.
	 *
	 * @return bool
	 */
	public function has_merchant_payment_upon_trigger_enabled() {
		foreach ( $this->alma_settings->allowed_fee_plans as $fee_plan ) {
			if ( self::is_payment_upon_trigger_enabled_for_fee_plan( $fee_plan ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells if a fee plan is allowed to accept "payment upon trigger" on admin alma dashboard.
	 *
	 * @param FeePlan $fee_plan A fee plan.
	 * @return bool
	 */
	public static function is_payment_upon_trigger_enabled_for_fee_plan( $fee_plan ) {
		return $fee_plan->deferred_trigger_limit_days > 0;
	}

	/**
	 * Tells if a fee plan definition (and not a FeePlan object) should do "payment upon trigger" depending on back-office configuration.
	 *
	 * @param FeePlan $fee_plan A fee plan definition.
	 * @return bool
	 */
	public function does_payment_upon_trigger_apply_for_this_fee_plan( $fee_plan ) {
		return 'yes' === $this->alma_settings->payment_upon_trigger_enabled &&
			in_array( $fee_plan->getInstallmentsCount(), array( 2, 3, 4 ), true ) &&
			0 === $fee_plan->getDeferredDays() &&
			0 === $fee_plan->getDeferredMonths();
	}
}
