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
class PnxGateway extends AbstractFrontendGateway {
	public const GATEWAY_TYPE = 'pnx';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay in installments with Alma';
		$this->method_title = L10nHelper::__( 'Payment in installments with Alma - 2x 3x 4x' );

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
			'alma_pnx_gateway_nonce_field',
			'alma_pnx_gateway_nonce_action'
		);

		// phpcs:ignore
		if ( ! $this->check_values( $_POST['alma_deferred'], array( '2', '3', '4' ) ) ) {
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
			'pnx-gateway-options.php',
			array( 'alma_woocommerce_gateway_fee_plan_list' => $this->get_fee_plan_list() ),
			'partials'
		);
	}

	/**
	 * Process payment fields and update order metadata.
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 */
	protected function process_payment_fields( WC_Order $order ): array {

		WordPressProxy::check_nonce(
			'alma_pnx_gateway_nonce_field',
			'alma_pnx_gateway_nonce_action'
		);

		$alma_installments = (int) sanitize_text_field( $_POST['alma_installments'] ?? 0 );// phpcs:ignore
		$order->update_meta_data( '_alma_installments', $alma_installments );
		$order->save();

		return array(
			'installments_count' => $alma_installments,
		);
	}
}
