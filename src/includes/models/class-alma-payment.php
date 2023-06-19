<?php
/**
 * Alma_Payment_Helper.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/models
 * @namespace Alma\Woocommerce\Models
 */

namespace Alma\Woocommerce\Models;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\API\Entities\FeePlan;
use Alma\API\Entities\Order;
use Alma\Woocommerce\Admin\Helpers\Alma_Order_Helper;
use Alma\Woocommerce\Helpers\Alma_Tools_Helper;
use Alma\Woocommerce\Alma_Logger;
use Alma\Woocommerce\Alma_Payment_Upon_Trigger;
use Alma\Woocommerce\Alma_Settings;
use Alma\Woocommerce\Helpers\Alma_Constants_Helper;

/**
 * Alma_Payment_Helper
 */
class Alma_Payment {


	/**
	 * The Logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;

	/**
	 * Payment upon trigger.
	 *
	 * @var Alma_Payment_Upon_Trigger
	 */
	protected $payment_upon_trigger;

	/**
	 * The db settings.
	 *
	 * @var Alma_Settings
	 */
	protected $alma_settings;

	/**
	 * The tool helper.
	 *
	 * @var Alma_Tools_Helper
	 */
	protected $tool_helper;

	/**
	 * The cart.
	 *
	 * @var Alma_Cart
	 */
	protected $cart;

	/**
	 * @var Alma_Order_Helper
	 */
	protected $order_helper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger               = new Alma_Logger();
		$this->payment_upon_trigger = new Alma_Payment_Upon_Trigger();
		$this->alma_settings        = new Alma_Settings();
		$this->tool_helper          = new Alma_Tools_Helper();
		$this->cart                 = new Alma_Cart();
		$this->order_helper         = new Alma_Order_Helper();
	}

	/**
	 * Create Eligibility data for Alma API request from WooCommerce Cart.
	 *
	 * @return array Payload to request eligibility v2 endpoint.
	 */
	public static function get_eligibility_payload_from_cart() {
		$cart     = new Alma_Cart();
		$customer = new Alma_Customer();
		$settings = new Alma_Settings();

		$data = array(
			'purchase_amount' => $cart->get_total_in_cents(),
			'queries'         => $settings->get_eligible_plans_for_cart(),
			'locale'          => apply_filters( 'alma_eligibility_user_locale', get_locale() ),
		);

		$billing_country  = $customer->get_billing_country();
		$shipping_country = $customer->get_shipping_country();

		if ( $billing_country ) {
			$data['billing_address'] = array( 'country' => $billing_country );
		}
		if ( $shipping_country ) {
			$data['shipping_address'] = array( 'country' => $shipping_country );
		}

		return $data;
	}

	/**
	 * Create Payment data for Alma API request from WooCommerce Order.
	 *
	 * @param int     $order_id Order ID.
	 * @param FeePlan $fee_plan Fee plan definition.
	 * @param string  $payment_type The payment type.
	 *
	 * @return array
	 */
	public function get_payment_payload_from_order( $order_id, $fee_plan, $payment_type ) {

		try {
			$order = new Alma_Order( $order_id );

			$wc_order = $order->get_order();
			$wc_order->add_order_note( $payment_type );

			$data = $this->build_data_for_alma( $order, $wc_order, $fee_plan );

		} catch ( \Exception $e ) {
			$this->logger->error(
				sprintf(
					'Error getting payment info from order id %s. Message : %s',
					$order_id,
					$e->getMessage()
				)
			);

			return array();
		}

		return apply_filters( 'alma_get_payment_payload_from_order', $data );
	}

	/**
	 * Build the data to sent to the Alma Api.
	 *
	 * @param Alma_Order $order The Alma order.
	 * @param \WC_Order  $wc_order The wc order.
	 * @param FeePlan    $fee_plan Fee plan definition.
	 * @return array
	 */
	protected function build_data_for_alma( $order, $wc_order, $fee_plan ) {
		$billing_address  = $order->get_billing_address( $wc_order );
		$shipping_address = $order->get_shipping_address( $wc_order );

		return array(
			'payment'                  => $this->build_payment_details( $order, $wc_order, $fee_plan, $billing_address, $shipping_address ),
			'order'                    => $this->build_order_details( $order ),
			'customer'                 => $this->build_customer_details( $order, $billing_address, $shipping_address ),
			'website_customer_details' => $this->build_website_customer_details( $order ),
		);
	}

	/**
	 * Build payment payload.
	 *
	 * @param Alma_Order $order The Alma order.
	 * @param \WC_Order $wc_order The WC order.
	 * @param FeePlan    $fee_plan The Fee Plan.
	 * @param array      $billing_address The Billing address.
	 * @return array
	 */
	protected function build_payment_details( $order, $wc_order, $fee_plan, $billing_address = array(), $shipping_address = array() ) {

		$data = array(
			'purchase_amount'     => $order->get_total_in_cent(),
			'return_url'          => $this->tool_helper->url_for_webhook( Alma_Constants_Helper::CUSTOMER_RETURN ),
			'ipn_callback_url'    => $this->tool_helper->url_for_webhook( Alma_Constants_Helper::IPN_CALLBACK ),
			'customer_cancel_url' => wc_get_checkout_url(),
			'installments_count'  => $fee_plan->getInstallmentsCount(),
			'deferred_days'       => $fee_plan->getDeferredDays(),
			'deferred_months'     => $fee_plan->getDeferredMonths(),
			'custom_data'         => array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
			),
			'locale'              => apply_filters( 'alma_checkout_payment_user_locale', get_locale() ),
			'cart'                => array(
				'items' => $this->get_previous_order_items_details( $wc_order, $fee_plan, true ),
			),
			'billing_address'     => $billing_address,
			'shipping_address'    => $shipping_address,
		);

		if ( $this->payment_upon_trigger->does_payment_upon_trigger_apply_for_this_fee_plan( $fee_plan ) ) {
			$data['deferred']             = 'trigger';
			$data['deferred_description'] = $this->alma_settings->get_display_text();
		}

		return $data;
	}

	/**
	 * Build order payload.
	 *
     * @param Alma_Order $order The Alma order.
	 * @param \WC_Order $wc_order The WC order.
	 * @return array
	 */
	protected function build_order_details( $wc_order, $order ) {
		return array(
			'merchant_reference' => $wc_order->get_order_number(),
			'merchant_url'       => $order->get_merchant_url(),
			'customer_url'       => $wc_order->get_view_order_url(),
		);
	}

	/**
	 * Build customer payload.
	 *
	 * @param Alma_Order $order The Alma order.
     * @param \WC_Order $wc_order The WC order.
	 * @param array      $billing_address The billing address.
	 * @return array
	 */
	protected function build_customer_details( $order, $wc_order, $billing_address = array(), $shipping_address = array() ) {
		$is_business = $order->is_business($wc_order);

		$data = array(
			'addresses'   => array(),
			'is_business' => $is_business,
		);

		if ( ! empty( $billing_address ) ) {
			$data['first_name']  = $billing_address['first_name'];
			$data['last_name']   = $billing_address['last_name'];
			$data['email']       = $billing_address['email'];
			$data['phone']       = $billing_address['phone'];
			$data['addresses'][] = $billing_address;

			if ( $is_business ) {
				$data['business_name'] = $wc_order->get_billing_company();
			}
		}

		if ( ! empty( $shipping_address ) ) {
			$data['addresses'][] = $shipping_address;
		}

		return $data;
	}

	/**
	 * Website Customer Details
	 *
	 * @param Alma_Order $order The Alma order.
	 * @return void
	 */
	protected function build_website_customer_details( $alma_order ) {
		/**
		 * @var \WC_Order $order The WC order.
		 */
		$order       = $alma_order->get_order();
		$customer_id = $order->get_customer_id();
		$is_guest    = false;

		if ( '0' == $customer_id ) {
			$is_guest = true;
		}

		$var_temp = array(
			'is_guest'        => $is_guest,
			'previous_orders' => $this->get_previous_orders_details( $customer_id, $is_guest ),
		);

		return $var_temp;
	}

	/**
	 * @param $order
	 * @param $is_guest
	 * @return void
	 */
	protected function get_previous_orders_details( $customer_id, $is_guest = true ) {
		if ( $is_guest ) {
			return array();
		}

		$orders = $this->order_helper->get_orders_by_customer_id( $customer_id );

		$order_details = array();

		foreach ( $orders as $order ) {
			$order_details[] = $this->get_previous_order_details( $order );
		}

		return $order_details;
	}

	/**
	 * @param \WC_Order $order The order.
	 * @return array
	 */
	protected function get_previous_order_details( $order ) {
		return array(
			'purchase_amount' => $this->tool_helper->alma_price_to_cents( $order->get_total() ),
			'payment_method'  => $order->get_payment_method(),
			'shipping_method' => $order->get_shipping_method(),
			'created'         => $order->get_date_created()->getTimestamp(),
			'items'           => $this->get_previous_order_items_details( $order ),
		);
	}

	/**
	 * Retrieve the X past purchase item details.
	 *
	 * @param \WC_Order $order The order.
	 * @param FeePlan   $fee_plan The Fee Plan.
	 * @param bool      $check_credit Check for payment payload if we are in credit.
	 * @return array
	 */
	protected function get_previous_order_items_details( $order, $fee_plan = array(), $check_credit = false ) {
		if (
			$check_credit
			&& ! $this->alma_settings->is_pnx_plus_4( $fee_plan )
		) {
			return array();
		}

		$items = $order->get_items();

		$item_details = array();

		foreach ( $items as $item ) {
			$item_details[] = $this->add_product_data( $item );
		}

		return $item_details;
	}

	/**
	 * Add details of one product.
	 *
	 * @param \WC_Order_Item $item The item order.
	 *
	 * @return array
	 */
	protected function add_product_data( $item ) {
		// @var \WC_Order_Item_Product $product_item The product.
		$product = $item->get_product();

		$categories = explode( ',', wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ',' ) ) );

		return array(
			'sku'               => $product->get_sku(),
			'title'             => $item->get_name(),
			'quantity'          => $item->get_quantity(),
			'unit_price'        => $this->tool_helper->alma_price_to_cents( $product->get_price() ),
			'line_price'        => $this->tool_helper->alma_price_to_cents( $item->get_total() ),
			'categories'        => $categories,
			'url'               => $product->get_permalink(),
			'picture_url'       => wp_get_attachment_url( $product->get_image_id() ),
			'requires_shipping' => $product->needs_shipping(),
		);
	}
}
