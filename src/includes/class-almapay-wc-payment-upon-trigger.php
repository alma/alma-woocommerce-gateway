<?php
/**
 * Alma Payment Upon Trigger
 *
 * @package Alma_WooCommerce_Gateway
 */

use Alma\API\Entities\FeePlan;
use Alma\API\RequestError;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Almapay_WC_Payment_Upon_Trigger
 */
class Almapay_WC_Payment_Upon_Trigger {

	/**
	 * Logger
	 *
	 * @var Almapay_WC_Logger
	 */
	private $logger;

	/**
	 * Order Statuses without "wc-" prefix
	 *
	 * @var array
	 */
	private static $order_statuses = array();

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger = new Almapay_WC_Logger();
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

		$order = wc_get_order( $order_id );
		if ( 'alma' !== $order->get_payment_method() ) {
			return;
		}

		if ( ! get_post_meta( $order_id, 'alma_payment_upon_trigger_enabled' ) ) {
			return;
		}

		if ( almapay_wc_plugin()->settings->payment_upon_trigger_event === $next_status ) {
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
	private function trigger_payment( $order_id, $next_status ) {

		$alma = almapay_wc_plugin()->get_alma_client();
		if ( ! $alma ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order->get_transaction_id() ) {
			$this->logger->error( 'Error while getting transaction_id on trigger_payment for order_id = ' . $order_id );
			return;
		}

		try {
			$payment = $alma->payments->fetch( $order->get_transaction_id() );
		} catch ( RequestError $e ) {
			$this->logger->error( sprintf( 'Fail to fetch payment with transaction_id %s for order_id %s ', $order->get_transaction_id(), $order_id ) );
			return;
		}

		if ( $payment->deferred_trigger_applied ) {
			$this->logger->info( 'Order number ' . $order_id . ' was already triggered : -->' . $payment->deferred_trigger_applied . '<--.' );
		} else {
			try {
				$alma->payments->trigger( $order->get_transaction_id() );
				// translators: %s: An order status (example: "completed").
				$order->add_order_note( sprintf( __( 'The first customer payment has been triggered, as you updated the order status to "%s".', 'alma-gateway-for-woocommerce' ), $next_status ) );
			} catch ( RequestError $e ) {
				$this->logger->log_stack_trace( 'Error while trigger payment for order number : ' . $order_id, $e );
			}
		}
	}

	/**
	 * Returns the lists of existing order statuses, without "wc-" prefix because the WC hook "woocommerce_order_status_changed" passes the order status without prefix.
	 *
	 * @return array
	 */
	public static function get_order_statuses() {
		if ( empty( self::$order_statuses ) ) {
			foreach ( wc_get_order_statuses() as $status_key => $status_description ) {
				self::$order_statuses[ str_replace( 'wc-', '', $status_key ) ] = $status_description;
			}
		}
		return self::$order_statuses;
	}

	/**
	 * Returns the list of texts proposed to be displayed on front-office.
	 *
	 * @return string
	 */
	public static function get_display_text() {
		return self::get_display_texts_keys_and_values() [ almapay_wc_plugin()->settings->payment_upon_trigger_display_text ];
	}

	/**
	 * Returns the list of texts proposed to be displayed on front-office.
	 *
	 * @return array
	 */
	public static function get_display_texts_keys_and_values() {
		return array(
			'at_shipping' => __( 'At shipping', 'alma-gateway-for-woocommerce' ),
		);
	}

	/**
	 * Has the merchant the "payment upon trigger" enabled in his admin alma dashboard.
	 *
	 * @return bool
	 */
	public static function has_merchant_payment_upon_trigger_enabled() {
		foreach ( almapay_wc_plugin()->settings->get_allowed_fee_plans() as $fee_plan ) {
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
	 * @param array $fee_plan_definition A fee plan definition.
	 * @return bool
	 */
	public static function does_payment_upon_trigger_apply_for_this_fee_plan( $fee_plan_definition ) {
		return 'yes' === almapay_wc_plugin()->settings->payment_upon_trigger_enabled &&
			in_array( $fee_plan_definition['installments_count'], array( 2, 3, 4 ), true ) &&
			0 === $fee_plan_definition['deferred_days'] &&
			0 === $fee_plan_definition['deferred_months'];
	}
}
