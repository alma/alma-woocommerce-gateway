<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\ValueObject\PaymentMethod;
use Alma\Gateway\Application\Exception\Helper\TemplateHelperException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 * @see public/templates/partials/pay-now-gateway-options.php for rendering
 */
class PayNowGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {

	public const GATEWAY_TYPE = PaymentMethod::PAY_NOW;

	/**
	 * Gateway constructor.
	 */
	public function __construct() {
		$this->title        = 'Pay now with Alma';
		$this->method_title = L10nHelper::__( 'Payment with Alma' );

		parent::__construct();
	}

	/**
	 * Check if the gateway is a pay now gateway.
	 *
	 * @return bool
	 */
	public function is_pay_now(): bool {
		return true;
	}

	/**
	 * Expose the payment fields to the frontend.
	 *
	 * @return void
	 * @throws TemplateHelperException
	 */
	public function payment_fields() {

		/** @var ConfigService $config_service */
		$config_service = Plugin::get_container()->get( ConfigService::class );
		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$template_helper->getTemplate(
			'paynow-gateway-options.php',
			array(
				'alma_woocommerce_gateway_nonce'       => $this->form_helper->generateTokenField(
					sprintf( '%s_nonce_action', $this->get_name() ),
					sprintf( '%s_nonce_field', $this->get_name() ),
				),
				'alma_woocommerce_gateway_merchant_id' => $config_service->getMerchantId(),
				'alma_woocommerce_gateway_in_page_iframe_selector' => sprintf(
					'alma_%s_gateway_in_page',
					$this->get_type()
				),
			),
			'partials'
		);
		wp_localize_script(
			'alma-frontend-in-page-implementation',
			'alma_woocommerce_gateway_pay_now_gateway',
			array(
				'type'         => $this->get_type(),
				'gateway_name' => sprintf( 'alma_%s_gateway', $this->get_type() ),
			)
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
