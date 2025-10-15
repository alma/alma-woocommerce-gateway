<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\Gateway\Application\Exception\Helper\TemplateHelperException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayNowGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {

	public const GATEWAY_TYPE = 'paynow';

	/**
	 * Gateway constructor.
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
	 * @throws TemplateHelperException
	 */
	public function payment_fields() {
		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$template_helper->getTemplate(
			'paynow-gateway-options.php',
			array(
				'alma_woocommerce_gateway_nonce' => $this->form_helper->generateTokenField(
					sprintf( '%s_nonce_action', $this->get_name() ),
					sprintf( '%s_nonce_field', $this->get_name() ),
				),
			),
			'partials'
		);
	}

	/**
	 * Process payment fields and update order metadata.
	 *
	 * @param OrderAdapterInterface $order The order to pay
	 *
	 * @return array
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 */
	public function process_payment_fields( OrderAdapterInterface $order ): array {

		$this->form_helper->validateTokenField(
			sprintf( '%s_nonce_action', $this->get_name() ),
			sprintf( '%s_nonce_field', $this->get_name() ),
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
