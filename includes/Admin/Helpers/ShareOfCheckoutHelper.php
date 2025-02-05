<?php
/**
 * ShareOfCheckoutHelper.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Admin/Helpers
 * @namespace Alma\Woocommerce\Admin\Helpers
 */

namespace Alma\Woocommerce\Admin\Helpers;

use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\ToolsHelperBuilder;
use Alma\Woocommerce\Exceptions\ApiSocLastUpdateDatesException;
use Alma\Woocommerce\Helpers\OrderHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * ShareOfCheckoutHelper
 */
class ShareOfCheckoutHelper {
	/**
	 * The alma settings.
	 *
	 * @var AlmaSettings
	 */
	protected $alma_settings;

	/**
	 * The order helper.
	 *
	 * @var OrderHelper
	 */
	protected $order_helper;

	/**
	 * Helper global.
	 *
	 * @var ToolsHelper
	 */
	protected $tool_helper;

	/**
	 * The legal helper.
	 *
	 * @var CheckLegalHelper
	 */
	protected $check_legal_helper;


	/**
	 * Construct.
	 */
	public function __construct() {
		$this->alma_settings      = new AlmaSettings();
		$this->order_helper       = new OrderHelper();
		$tools_helper_builder     = new ToolsHelperBuilder();
		$this->tool_helper        = $tools_helper_builder->get_instance();
		$this->check_legal_helper = new CheckLegalHelper();
	}


	/**
	 * Returns the date of the last share of checkout.
	 *
	 * @return false|string
	 * @throws ApiSocLastUpdateDatesException Date Exception.
	 */
	public function get_last_update_date() {
		$last_update_by_api = $this->alma_settings->get_soc_last_updated_date();

		return gmdate( 'Y-m-d', $last_update_by_api['end_time'] );
	}

	/**
	 * Returns the default date of the last share of checkout.
	 *
	 * @return false|string
	 */
	public function get_default_last_update_date() {
		return gmdate( 'Y-m-d', strtotime( '-2 days' ) );
	}

	/**
	 * Gets the payload to send to API.
	 *
	 * @param string $from_date The start date yyyy-mm-dd formatted.
	 * @param string $end_date The end date yyyy-mm-dd formatted.
	 *
	 * @return array
	 */
	public function get_payload( $from_date, $end_date ) {
		$orders_by_date_range = $this->order_helper->get_orders_by_date_range( $from_date, $end_date );
		$from_date            = $from_date . ' 00:00:00';
		$end_date             = $end_date . ' 23:59:59';

		if ( 0 === count( $orders_by_date_range ) ) {
			return array();
		}

		return array(
			'start_time'      => $from_date,
			'end_time'        => $end_date,
			'orders'          => $this->get_payload_orders( $orders_by_date_range ),
			'payment_methods' => $this->get_payload_payment_methods( $orders_by_date_range ),
		);
	}

	/**
	 * Gets the "orders" payload.
	 *
	 * @param array $orders_by_date_range Array of WC orders.
	 *
	 * @return array
	 */
	protected function get_payload_orders( $orders_by_date_range ) {
		$order_currencies = array();

		foreach ( $orders_by_date_range as $wc_order ) {
			/**
			 * The wc order.
			 *
			 * @var \WC_Order $wc_order The wc order.
			 */

			if ( ! isset( $order_currencies[ $wc_order->get_currency() ] ) ) {
				$order_currencies[ $wc_order->get_currency() ] = array(
					'total_order_count' => 0,
					'total_amount'      => 0,
					'currency'          => $wc_order->get_currency(),
				);
			}

			$currency = $wc_order->get_currency();
			$order_currencies[ $currency ]['total_order_count'] += 1;
			$order_currencies[ $currency ]['total_amount']      += $this->tool_helper->alma_price_to_cents( $wc_order->get_total() );
		}

		return array_values( $order_currencies );
	}

	/**
	 * Gets the "payment_methods" payload.
	 *
	 * @param array $orders_by_date_range Array of WC orders.
	 *
	 * @return array
	 */
	protected function get_payload_payment_methods( $orders_by_date_range ) {
		$payment_methods_currencies = array();
		foreach ( $orders_by_date_range as $wc_order ) {
			/**
			 * The wc order.
			 *
			 * @var \WC_Order $wc_order The wc order.
			 */
			if ( ! isset( $payment_methods_currencies[ $wc_order->get_payment_method() ] ) ) {
				$payment_methods_currencies[ $wc_order->get_payment_method() ] = array();
			}
			if ( ! isset( $payment_methods_currencies[ $wc_order->get_payment_method() ][ $wc_order->get_currency() ] ) ) {
				$payment_methods_currencies[ $wc_order->get_payment_method() ][ $wc_order->get_currency() ] = array(
					'order_count' => 0,
					'amount'      => 0,
				);
			}
			$payment_methods_currencies[ $wc_order->get_payment_method() ][ $wc_order->get_currency() ]['order_count'] += 1;
			$payment_methods_currencies[ $wc_order->get_payment_method() ][ $wc_order->get_currency() ]['amount']      += $this->tool_helper->alma_price_to_cents( $wc_order->get_total() );
		}

		$payment_methods = array();
		foreach ( $payment_methods_currencies as $payment_method_name => $currency_values ) {
			$payment_method                        = array();
			$payment_method['payment_method_name'] = $payment_method_name;
			$orders                                = array();
			foreach ( $currency_values as $currency => $values ) {
				$orders[] = array(
					'order_count' => $values['order_count'],
					'amount'      => $values['amount'],
					'currency'    => $currency,
				);
			}
			$payment_method['orders'] = $orders;
			$payment_methods[]        = $payment_method;
		}

		return $payment_methods;
	}

	/**
	 * Verify if the soc value has changed.
	 *
	 * @param array $post_data The data.
	 *
	 * @return bool
	 */
	public function soc_has_changed( $post_data ) {
		if (
			(
				isset( $post_data['woocommerce_alma_share_of_checkout_enabled'] )
				&& '1' === $post_data['woocommerce_alma_share_of_checkout_enabled']
				&& 'no' === $this->alma_settings->__get( 'share_of_checkout_enabled' )
			)
			|| (
				! isset( $post_data['woocommerce_alma_share_of_checkout_enabled'] )
				&& 'yes' === $this->alma_settings->__get( 'share_of_checkout_enabled' )
			)
			|| $this->alma_settings->__get( 'live_api_key' ) !== $post_data['woocommerce_alma_live_api_key']
			|| $post_data['woocommerce_alma_environment'] !== $this->alma_settings->get_environment()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Process the checkout legal data.
	 *
	 * @param array $post_data The data.
	 * @param array $settings The settings.
	 *
	 * @return array
	 */
	public function process_checkout_legal( $post_data, $settings ) {

		// By default, remove api consent.
		$value = 'no';

		// Check if the live_api_key has changed. Remove the consent.
		if (
			$this->alma_settings->__get( 'live_api_key' ) !== $post_data['woocommerce_alma_live_api_key']
		) {
			$settings['share_of_checkout_enabled_date']             = '';
			$settings['woocommerce_alma_share_of_checkout_enabled'] = '';
		} elseif (
			isset( $post_data['woocommerce_alma_share_of_checkout_enabled'] )
			&& '1' === $post_data['woocommerce_alma_share_of_checkout_enabled']
			&& 'live' === $post_data['woocommerce_alma_environment']
		) {
			$settings['share_of_checkout_enabled_date'] = gmdate( 'Y-m-d' );

			$value = 'yes';
		}

		$this->check_legal_helper->send_consent( $value );

		if (
			'test' === $post_data['woocommerce_alma_environment']
			&& 'live' === $this->alma_settings->get_environment()
		) {
			delete_transient( 'alma-admin-soc-panel' );
		}

		return $settings;
	}
}
