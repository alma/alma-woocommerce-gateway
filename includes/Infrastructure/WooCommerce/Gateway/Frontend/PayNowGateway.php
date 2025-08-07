<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Gateway\Frontend;

use Alma\API\Domain\OrderInterface;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayNowGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {

	public const GATEWAY_TYPE = 'pay-now';

	/**
	 * Gateway constructor.
	 *
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay now with Alma';
		$this->method_title = L10nHelper::__( 'Payment with Alma' );

		parent::__construct();
	}

	/**
	 * Expose the payment fields to the frontend.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public function payment_fields() {
		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$template_helper->get_template(
			'pay-now-gateway-options.php',
			array(),
			'partials'
		);
	}

	/**
	 * Process payment fields and update order metadata.
	 *
	 * @param OrderInterface $order The order to pay
	 *
	 * @return array
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 */
	public function process_payment_fields( OrderInterface $order ): array {

		WordPressProxy::check_nonce(
			'alma_pay_now_gateway_nonce_field',
			'alma_pay_now_gateway_nonce_action'
		);

		// @todo Add validation for the payment fields.
		// phpcs:ignore
		$alma_plan_key = $_POST['alma_plan_key'] ?? 'general_1_0_0';
		$order->update_meta_data( '_alma_plan_key', $alma_plan_key );
		$order->save();

		return array(
			'alma_plan_key' => $alma_plan_key,
		);
	}
}
