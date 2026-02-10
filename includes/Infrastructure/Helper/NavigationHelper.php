<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;
use Alma\Plugin\Infrastructure\Helper\NavigationHelperInterface;

class NavigationHelper implements NavigationHelperInterface {

	/**
	 * Redirect to Alma gateway settings page.
	 */
	public static function alma_redirect_to_gateway_settings() {
		$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=alma_config_gateway' );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirects to the return URL after payment.
	 * This method is used to redirect the user to the return URL after a successful payment.
	 * It retrieves the return URL from the payment method and redirects the user to that URL.
	 * If the return URL is not set, it falls back to the cart URL.
	 *
	 * @param OrderAdapterInterface $order The order object containing the payment method and return URL.
	 *
	 * @return void
	 */
	public function redirectAfterPayment( OrderAdapterInterface $order ): void {

		// Get the return url from the payment method
		$payment_method = $order->getWcOrder()->get_payment_method();
		$url            = WC()->payment_gateways()->payment_gateways()[ $payment_method ]->get_return_url( $order->getWcOrder() );
		// If the return url is not set, fallback to the cart url
		if ( ! $url ) {
			$url = wc_get_cart_url();
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirect to the cart page with an optional message.
	 *
	 * @param string|null $message The message to display on the cart page.
	 *
	 */
	public function redirectToCart( $message = null ): void {
		if ( $message ) {
			ShopNotificationHelper::notifyError( $message );
		}
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}
}
