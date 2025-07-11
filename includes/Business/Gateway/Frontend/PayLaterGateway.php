<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Exception\MerchantServiceException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\TemplateHelper;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;
use WC_Order;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayLaterGateway extends AbstractFrontendGateway {

	public const GATEWAY_TYPE = 'pay-later';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay later with Alma';
		$this->method_title = L10nHelper::__( 'Payment deferred with Alma' );

		parent::__construct();
	}

	/**
	 * Validate the fields submitted by the user.
	 *
	 * @return bool
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 */
	public function validate_fields(): bool {

		WordPressProxy::check_nonce(
			'alma_pay-later_gateway_nonce_field',
			'alma_pay-later_gateway_nonce_action'
		);

		// phpcs:ignore
		if ( ! $this->check_values( $_POST['alma_deferred'], array( '15_0', '30_0', '45_0', '0_1', '0_2', '0_3' ) ) ) {
			WooCommerceProxy::notify_error( L10nHelper::__( 'Please choose a valid option.' ) );

			return false;
		}

		return true;
	}

	/**
	 * Expose the payment fields to the frontend.
	 *
	 * @return void
	 * @throws ContainerException
	 * @throws MerchantServiceException
	 */
	public function payment_fields() {
		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$template_helper->get_template(
			'pay-later-gateway-options.php',
			array( 'alma_woocommerce_gateway_fee_plan_list' => $this->get_fee_plan_list() ),
			'partials'
		);
	}

	/**
	 * Process payment fields and update order metadata.
	 *
	 * @param WC_Order $order The order to pay
	 *
	 * @return array
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 */
	protected function process_payment_fields( WC_Order $order ): array {

		WordPressProxy::check_nonce(
			'alma_pnx_gateway_nonce_field',
			'alma_pnx_gateway_nonce_action'
		);

		$deferred = (int) sanitize_text_field( $_POST['alma_deferred'] ?? 0 );// phpcs:ignore
		list( $deferred_days, $deferred_months ) = explode( '_', $deferred );
		$order->update_meta_data( '_alma_deferred_days', $deferred_days );
		$order->update_meta_data( '_alma_deferred_months', $deferred_months );
		$order->save();

		return array(
			'deferred_days'   => $deferred_days,
			'deferred_months' => $deferred_months,
		);
	}
}
