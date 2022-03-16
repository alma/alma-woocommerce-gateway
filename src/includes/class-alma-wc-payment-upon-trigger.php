<?php
/**
 * Alma Payment Upon Trigger
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Payment_Upon_Trigger
 */
class Alma_WC_Payment_Upon_Trigger {

    const FOO = 'bar';

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * __construct.
	 */
	public function __construct() {
        add_action( 'woocommerce_order_status_changed', array( $this, 'woocommerce_order_status_changed' ), 10, 3 );
	}

    /**
     * Callback function for the event "order status changed".
     *
     * @param integer $order_id The order id.
     * @param string $previous_status Order status before it changes.
     * @param string $next_status Order status affected to the order.
     * @return void
     */
	public function woocommerce_order_status_changed( $order_id, $previous_status, $next_status ) {

        if ( 'yes'  !== alma_wc_plugin()->settings->payment_upon_trigger_enabled ) {
            return;
        }

        if ( $next_status === alma_wc_plugin()->settings->payment_upon_trigger_event ) {

            /*
            @check if order isn't flag as already paid.
            */

            $this->launch_payment( $order_id );
        }
	}

    /**
     * Launches the payment on trigger for an order.
     *
     * @param integer $order_id The order id.
     * @return void
     */
	private function launch_payment( $order_id ) {

        /*
        @flag order as already paid.
        */

        error_log('launch payment for the order_id = ' . $order_id);
	}

	/**
	 * Returns the lists of existing order statuses.
	 *
	 * @return array
	 */
	public static function get_order_statuses() {
		$get_order_statuses = wc_get_order_statuses();
        foreach ( $get_order_statuses as $status_key => $status_description ) {
            $get_order_statuses[ str_replace( 'wc-', '', $status_key ) ] = $status_description;
            unset( $get_order_statuses[ $status_key ] );
        }
        return $get_order_statuses;
	}

	/**
	 * Returns the list of texts proposed to be displayed on front-office.
	 *
	 * @return array
	 */
	public static function get_display_texts() {
		return array(
            'payment_on_order_create' => __( 'Payment on order creation' , 'alma-woocommerce-gateway' ),
            'payment_on_shipping'     => __( 'Payment on shipping' , 'alma-woocommerce-gateway' )
        );
	}

}
//
//^ array:7 [▼
//  "pending" => "Pending payment"
//  "processing" => "Processing"
//  "on-hold" => "On hold"
//  "completed" => "Completed"
//  "cancelled" => "Cancelled"
//  "refunded" => "Refunded"
//  "failed" => "Failed"
//]

new Alma_WC_Payment_Upon_Trigger();