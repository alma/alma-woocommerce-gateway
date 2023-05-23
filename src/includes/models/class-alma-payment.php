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
	 * Constructor.
	 */
	public function __construct() {
		$this->logger               = new Alma_Logger();
		$this->payment_upon_trigger = new Alma_Payment_Upon_Trigger();
		$this->alma_settings        = new Alma_Settings();
		$this->tool_helper          = new Alma_Tools_Helper();
		$this->cart                 = new Alma_Cart();
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

			$data = $this->build_data_for_alma( $order, $fee_plan );

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
	 * @param Alma_Order $order The order.
	 * @param FeePlan    $fee_plan Fee plan definition.
	 * @return array|array[]
	 */
	protected function build_data_for_alma( $order, $fee_plan ) {
		$data = array(
			'payment'  => array(
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
					'items' => array(),
				),
			),
			'order'    => array(
				'merchant_reference' => $order->get_order_reference(),
				'merchant_url'       => $order->get_merchant_url(),
				'customer_url'       => $order->get_customer_url(),
			),
			'customer' => array(
				'addresses'   => array(),
				'is_business' => $order->is_business(),
			),
		);

		$data = $this->add_upon_trigger_data( $data, $fee_plan );
		$data = $this->add_billing_address_data( $data, $order );
		$data = $this->add_shipping_address_data( $data, $order );

		if ( $this->alma_settings->is_pnx_plus_4( $fee_plan ) ) {
			$data = $this->add_products_data( $data, $order->get_order() );
		}

		return $data;
	}


	/**
	 * Add details of the products.
	 *
	 * @param array     $data The payload.
	 * @param \WC_Order $order The order.
	 * @return array
	 */
	protected function add_products_data( $data, $order ) {
		$items = $order->get_items();

		foreach ( $items as $item ) {
			$data['payment']['cart']['items'][] = $this->add_product_data( $item );
		}

		return $data;
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

	/**
	 * Add shipping address data.
	 *
	 * @param array      $data The paypload.
	 * @param Alma_Order $order The order.
	 * @return array
	 */
	protected function add_shipping_address_data( $data, $order ) {
		// Shipping address.
		if ( $order->has_shipping_address() ) {
			$shipping_address                    = $order->get_shipping_address();
			$data['payment']['shipping_address'] = $shipping_address;
			$data['customer']['addresses'][]     = $shipping_address;
		}

		return $data;
	}

	/**
	 * Add billing address data.
	 *
	 * @param array      $data The paypload.
	 * @param Alma_Order $order The order.
	 * @return array
	 */
	protected function add_billing_address_data( $data, $order ) {
		// Billing address.
		if ( $order->has_billing_address() ) {
			$billing_address                    = $order->get_billing_address();
			$data['payment']['billing_address'] = $billing_address;

			$data['customer']['first_name']  = $billing_address['first_name'];
			$data['customer']['last_name']   = $billing_address['last_name'];
			$data['customer']['email']       = $billing_address['email'];
			$data['customer']['phone']       = $billing_address['phone'];
			$data['customer']['addresses'][] = $billing_address;

			if ( $order->is_business() ) {
				$data['customer']['business_name'] = $order->get_business_name();
			}
		}

		return $data;
	}

	/**
	 * Add uppon trigger data.
	 *
	 * @param array   $data The data.
	 * @param FeePlan $fee_plan The plan definition.
	 * @return array
	 */
	protected function add_upon_trigger_data( $data, $fee_plan ) {
		// Payment upon trigger.
		if ( $this->payment_upon_trigger->does_payment_upon_trigger_apply_for_this_fee_plan( $fee_plan ) ) {
			$data['payment']['deferred']             = 'trigger';
			$data['payment']['deferred_description'] = $this->alma_settings->get_display_text();
		}

		return $data;
	}
}
