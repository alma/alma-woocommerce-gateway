<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\API\Domain\ValueObject\PaymentMethod;
use Alma\Gateway\Application\Exception\Helper\TemplateHelperException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\NotificationHelper;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 * @see public/templates/partials/pnx-gateway-options.php for rendering
 */
class PnxGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {
	public const PAYMENT_METHOD    = PaymentMethod::PNX;
	public const TITLE_FIELD       = self::PAYMENT_METHOD . '_title_field';
	public const DESCRIPTION_FIELD = self::PAYMENT_METHOD . '_description_field';
	private ConfigService $config_service;

	/**
	 * Gateway constructor.
	 */
	public function __construct( ConfigService $config_service ) {
		$this->config_service = $config_service;
		$this->title          = $config_service->getSetting( self::TITLE_FIELD );
		$this->description    = $config_service->getSetting( self::DESCRIPTION_FIELD );
		$this->method_title   = L10nHelper::__( 'Payment in installments with Alma - 2x 3x 4x' );

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
			sprintf( '%s_nonce_action', $this->get_name() ),
			sprintf( '%s_nonce_field', $this->get_name() ),
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
	 * @throws TemplateHelperException|FeePlanRepositoryException
	 */
	public function payment_fields() {

		/** @var ConfigService $config_service */
		$config_service = Plugin::get_container()->get( ConfigService::class );

		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$feePlanList     = $this->getFeePlanList();

		/** @var FeePlanAdapter $fee_plan_adapter */
		foreach ( $feePlanList as $fee_plan_adapter ) {
			$template_helper->getTemplate(
				'gateway-options.php',
				array(
					'alma_woocommerce_gateway_payment_method' => $this->get_payment_method(),
					'alma_woocommerce_gateway_plan_key'    => $fee_plan_adapter->getPlanKey(),
					'alma_woocommerce_gateway_logo_url'    => AssetsHelper::getImage( 'images/alma_card_logo.svg' ),
					'alma_woocommerce_gateway_fee_plan_label' => $fee_plan_adapter->getLabel(),
					'alma_woocommerce_gateway_description' => $this->description,
				),
				'partials'
			);
		}

		foreach ( $feePlanList as $fee_plan_adapter ) {
			$template_helper->getTemplate(
				'gateway-plans.php',
				array(
					'alma_woocommerce_gateway_payment_method' => $this->get_payment_method(),
					'alma_woocommerce_gateway_plan_key' => $fee_plan_adapter->getPlanKey(),
					'alma_woocommerce_gateway_name'     => $this->get_name(),
					'alma_woocommerce_gateway_fee_plan' => $fee_plan_adapter,
					'alma_woocommerce_gateway_in_page_enabled' => $config_service->isInPageEnabled(),
					'alma_woocommerce_gateway_in_page_iframe_selector' => sprintf(
						'alma_%s_gateway_in_page',
						$this->get_payment_method()
					),
					'alma_woocommerce_gateway_nonce'    => $this->form_helper->generateTokenField(
						sprintf( '%s_nonce_action', $this->get_name() ),
						sprintf( '%s_nonce_field', $this->get_name() ),
					),
				),
				'partials'
			);
		}
		wp_localize_script(
			'alma-in-page',
			'alma_woocommerce_gateway_pnx_gateway',
			array(
				'type'         => $this->get_payment_method(),
				'gateway_name' => sprintf( 'alma_%s_gateway', $this->get_payment_method() ),
				'amount'       => '',
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
		$alma_plan_key = $_POST['alma_plan_key'] ?? 'general_1_0_0'; // Default to general plan if not set.
		$order->update_meta_data( '_alma_plan_key', $alma_plan_key );
		$order->save();

		return array(
			'alma_plan_key' => $alma_plan_key,
		);
	}
}
