<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Gateway\Frontend;

use Alma\API\Domain\OrderInterface;
use Alma\Gateway\Application\Exception\ContainerException;
use Alma\Gateway\Application\Exception\MerchantServiceException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WooCommerceProxy;
use Alma\Gateway\Infrastructure\WooCommerce\Proxy\WordPressProxy;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayLaterGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {

	public const GATEWAY_TYPE = 'pay-later';

	/**
	 * Gateway constructor.
	 *
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
	 * @todo Check values against the available plans.
	 */
	public function validate_fields(): bool {

		WordPressProxy::check_nonce(
			'alma_pay-later_gateway_nonce_field',
			'alma_pay-later_gateway_nonce_action'
		);

		// phpcs:ignore
		if ( $_POST['alma_plan_key'] && ! $this->check_values( $_POST['alma_plan_key'],
			array(
				'general_1_15_0',
				'general_1_30_0',
				'general_1_45_0',
				'general_1_0_1',
				'general_1_0_2',
				'general_1_0_3',
			)
		) ) {
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
	 * @param OrderInterface $order The order to pay
	 *
	 * @return array
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 */
	public function process_payment_fields( OrderInterface $order ): array {

		WordPressProxy::check_nonce(
			'alma_pay_later_gateway_nonce_field',
			'alma_pay_later_gateway_nonce_action'
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
