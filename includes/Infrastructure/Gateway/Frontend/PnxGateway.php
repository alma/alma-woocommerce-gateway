<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\Exception\ContainerException;
use Alma\API\Domain\Exception\MerchantServiceException;
use Alma\API\Exception\ParametersException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PnxGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {
	public const GATEWAY_TYPE = 'pnx';

	/**
	 * Gateway constructor.
	 *
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay in installments with Alma';
		$this->method_title = L10nHelper::__( 'Payment in installments with Alma - 2x 3x 4x' );

		parent::__construct();
	}

	/**
	 * Validate the fields submitted by the user.
	 * @return bool
	 * @phpcs ignore verify_nonce because we use a proxy to check nonce
	 * @todo Check values against the available plans.
	 */
	public function validate_fields(): bool {

		$this->form_helper->validateTokenField(
			'alma_pnx_gateway_nonce_field',
			'alma_pnx_gateway_nonce_action'
		);

		// phpcs:ignore
		if ( $_POST['alma_plan_key'] && ! $this->check_values( $_POST['alma_plan_key'],
			array( 'general_2_0_0', 'general_3_0_0', 'general_4_0_0' )
		) ) {
			/** @var NotificationHelper $notificationHelper */
			$notificationHelper = Plugin::get_container()->get( NotificationHelper::class );
			$notificationHelper->notifyError( L10nHelper::__( 'Please choose a valid option.' ) );

			return false;
		}

		return true;
	}

	/**
	 * Expose the payment fields to the frontend.
	 *
	 * @return void
	 * @throws ContainerException
	 * @throws MerchantServiceException|ParametersException
	 */
	public function payment_fields() {
		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$template_helper->get_template(
			'pnx-gateway-options.php',
			array(
				'alma_woocommerce_gateway_fee_plan_list' => $this->getFeePlanList(),
				'alma_woocommerce_gateway_nonce'         => $this->form_helper->generateTokenField(
					'alma_pnx_gateway_nonce_action',
					'alma_pnx_gateway_nonce_field'
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
			'alma_pnx_gateway_nonce_field',
			'alma_pnx_gateway_nonce_action'
		);

		// @todo Add validation for the payment fields.
		// phpcs:ignore
		$alma_plan_key = $_POST['alma_plan_key'] ?? 'general_1_0_0'; // Default to general plan if not set.
		$order->update_meta_data( '_alma_plan_key', $alma_plan_key );
		$order->save();

		return array(
			'alma_plan_key' => $alma_plan_key,
		);
	}
}
