<?php
/**
 * CheckoutService.
 *
 * @since 5.0.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Services
 * @namespace Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Services;

use Alma\Woocommerce\Exceptions\AlmaException;
use Alma\Woocommerce\Helpers\CheckoutHelper;
use Alma\Woocommerce\Helpers\ConstantsHelper;
use Alma\Woocommerce\Helpers\PluginHelper;


if (!defined('ABSPATH')) {
	die('Not allowed'); // Exit if accessed directly.
}


/**
 * CheckoutService
 */
class CheckoutService extends \WC_Checkout
{



	/**
	 * The plugin helper.
	 *
	 * @var PluginHelper
	 */
	protected $plugin_helper;

	/**
	 * Construct.
	 */
	public function __construct()
	{
		$this->plugin_helper = new PluginHelper();
	}

	/**
	 * Extends \WC_Checkout.
	 *
	 * @return \WC_Order
	 * @throws AlmaException The exception.
	 * @throws \Exception Exception.
	 */
	public function process_checkout()
	{
		if (
			isset($_POST['is_woo_block'])
			&& $_POST['is_woo_block'] // phpcs:ignore WordPress.Security.NonceVerification
		) {
			foreach ($_POST['fields']['billing_address'] as $key => $value) { // phpcs:ignore WordPress.Security.NonceVerification
				$_POST['billing_' . $key] = $value;
				$_REQUEST['billing_' . $key] = $value;
			}
			unset($_POST['fields']['billing_address']);

			foreach ($_POST['fields']['shipping_address'] as $key => $value) { // phpcs:ignore WordPress.Security.NonceVerification
				$_POST['shipping_' . $key] = $value;
				$_REQUEST['shipping_' . $key] = $value;
			}

			unset($_POST['fields']['shipping_address']);

			if (isset($_POST['fields']['orderNotes'])) { // phpcs:ignore WordPress.Security.NonceVerification
				$_POST['order_comments'] = $_POST['fields']['orderNotes']; // phpcs:ignore WordPress.Security.NonceVerification
				$_REQUEST['order_comments'] = $_POST['fields']['orderNotes']; // phpcs:ignore WordPress.Security.NonceVerification
			}

			foreach ($_POST['fields'] as $key => $value) { // phpcs:ignore WordPress.Security.NonceVerification
				$_POST[$key] = $value;
				$_REQUEST[$key] = $value;
			}
			// var_dump($_POST);
			// die();
			unset($_POST['fields']);
		} else {
			foreach ($_POST['fields'] as $values) { // phpcs:ignore WordPress.Security.NonceVerification
				// Set each key / value pairs in an array.
				$_POST[$values['name']] = $values['value'];
				$_REQUEST[$values['name']] = $values['value'];
			}
		}


		$checkout_helper = new CheckoutHelper();
		$is_alma_payment = $checkout_helper->is_alma_payment_method($_POST[ConstantsHelper::PAYMENT_METHOD]); // phpcs:ignore WordPress.Security.NonceVerification

		if (!$is_alma_payment) {
			throw new AlmaException(__('We were unable to process your order, please try again.', 'alma-gateway-for-woocommerce'));
		}
		if (!$this->plugin_helper->has_woocommerce_blocks()) {
			$nonce_value = wc_get_var($_POST['woocommerce-process-checkout-nonce'], wc_get_var($_POST['_wpnonce'], '')); // @codingStandardsIgnoreLine.

			if (
				empty($nonce_value)
				||
				!wp_verify_nonce($nonce_value, 'woocommerce-process_checkout')
			) {
				WC()->session->set('refresh_totals', true);
				throw new AlmaException(__('We were unable to process your order, please try again.', 'alma-gateway-for-woocommerce'));
			}
		}

		wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);
		wc_set_time_limit(0);

		do_action('woocommerce_before_checkout_process'); // phpcs:ignore

		if (WC()->cart->is_empty()) {
			/* translators: %s: shop cart url */
			throw new AlmaException(sprintf(__('Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'alma-gateway-for-woocommerce'), esc_url(wc_get_page_permalink('shop'))));
		}

		do_action('woocommerce_checkout_process'); // phpcs:ignore

		$errors = new \WP_Error();
		$posted_data = $this->get_posted_data();

		if (ConstantsHelper::GATEWAY_ID_IN_PAGE === $_POST[ConstantsHelper::PAYMENT_METHOD]) {
			$posted_data[ConstantsHelper::PAYMENT_METHOD_TITLE] = __('Payment in installments via Alma', 'alma-gateway-for-woocommerce');
		}

		if (ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_NOW === $_POST[ConstantsHelper::PAYMENT_METHOD]) {
			$posted_data[ConstantsHelper::PAYMENT_METHOD_TITLE] = __('Payment by credit cart via Alma', 'alma-gateway-for-woocommerce');
		}
		if (ConstantsHelper::GATEWAY_ID_IN_PAGE_PAY_LATER === $_POST[ConstantsHelper::PAYMENT_METHOD]) {
			$posted_data[ConstantsHelper::PAYMENT_METHOD_TITLE] = __('Pay Later via Alma', 'alma-gateway-for-woocommerce');
		}

		// Update session for customer and totals.
		$this->update_session($posted_data);

		// Validate posted data and cart items before proceeding.
		$this->validate_checkout($posted_data, $errors);

		foreach ($errors->get_error_messages() as $message) {
			wc_add_notice($message, 'error');
		}

		if (
			empty($posted_data['woocommerce_checkout_update_totals'])
			&& 0 === wc_notice_count('error')
		) {
			$this->process_customer($posted_data);

			$order_id = $this->create_order($posted_data);
			$order = wc_get_order($order_id);

			if (is_wp_error($order_id)) {
				throw new AlmaException($order_id->get_error_message());
			}

			if (!$order) {
				throw new AlmaException(__('Unable to create order.', 'alma-gateway-for-woocommerce'));
			}

			do_action('woocommerce_checkout_order_processed', $order_id, $posted_data, $order); // phpcs:ignore
			return $order;
		}

		throw new AlmaException('An error occurred');
	}


	/**
	 * Validate checkout.
	 *
	 * @param array $data The data.
	 * @param array $errors The errors.
	 *
	 * @return void
	 */
	protected function validate_checkout(&$data, &$errors)
	{
		$this->validate_posted_data($data, $errors);
		$this->check_cart_items();

		if (
			empty($data['woocommerce_checkout_update_totals'])
			&& empty($data['terms'])
			&& !empty($_POST['terms-field']) // phpcs:ignore WordPress.Security.NonceVerification
		) { // WPCS: input var ok, CSRF ok.
			$errors->add('terms', __('Please read and accept the terms and conditions to proceed with your order.', 'alma-gateway-for-woocommerce'));
		}

		if (WC()->cart->needs_shipping()) {
			$shipping_country = WC()->customer->get_shipping_country();

			if (empty($shipping_country)) {
				$errors->add('shipping', __('Please enter an address to continue.', 'alma-gateway-for-woocommerce'));
			} elseif (!in_array(WC()->customer->get_shipping_country(), array_keys(WC()->countries->get_shipping_countries()), true)) {
				/* translators: %s: shipping location */
				$errors->add('shipping', sprintf(__('Unfortunately <strong>we do not ship %s</strong>. Please enter an alternative shipping address.', 'alma-gateway-for-woocommerce'), WC()->countries->shipping_to_prefix() . ' ' . WC()->customer->get_shipping_country()));
			} else {
				$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

				foreach (WC()->shipping->get_packages() as $i => $package) {
					if (!isset($chosen_shipping_methods[$i], $package['rates'][$chosen_shipping_methods[$i]])) {
						$errors->add('shipping', __('No shipping method has been selected. Please double check your address, or contact us if you need any help.', 'alma-gateway-for-woocommerce'));
					}
				}
			}
		}

		do_action('woocommerce_after_checkout_validation', $data, $errors); // phpcs:ignore
	}

}
