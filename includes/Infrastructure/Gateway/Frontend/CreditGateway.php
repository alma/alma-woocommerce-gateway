<?php

namespace Alma\Gateway\Infrastructure\Gateway\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Client\Domain\ValueObject\PaymentMethod;
use Alma\Gateway\Application\Exception\Helper\TemplateHelperException;
use Alma\Gateway\Application\Helper\TemplateHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Exception\Gateway\GatewayException;
use Alma\Gateway\Infrastructure\Helper\AssetsHelper;
use Alma\Gateway\Infrastructure\Helper\ShopNotificationHelper;
use Alma\Gateway\Plugin;
use Alma\Plugin\Infrastructure\Adapter\OrderAdapterInterface;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 * @see public/templates/partials/gateway-options.php for rendering
 */
class CreditGateway extends AbstractFrontendGateway implements FrontendGatewayInterface {

	public const PAYMENT_METHOD    = PaymentMethod::CREDIT;
	public const TITLE_FIELD       = self::PAYMENT_METHOD . '_title_field';
	public const DESCRIPTION_FIELD = self::PAYMENT_METHOD . '_description_field';

	/**
	 * Gateway constructor.
	 */
	public function __construct() {
		/** @var ConfigService $config_service */
		$config_service     = Plugin::get_container()->get( ConfigService::class );
		$this->title        = $config_service->hasSetting( self::TITLE_FIELD ) ? $config_service->getSetting( self::TITLE_FIELD ) : __(
			'Pay with financing',
			'alma-gateway-for-woocommerce'
		);
		$this->description  = $config_service->hasSetting( self::DESCRIPTION_FIELD ) ? $config_service->getSetting( self::DESCRIPTION_FIELD ) : __(
			'Fast and secure payment by credit card',
			'alma-gateway-for-woocommerce'
		);
		$this->method_title = __( 'Payment in installments with Alma - 10x 12x', 'alma-gateway-for-woocommerce' );

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

		$this->form_helper->validateTokenField(
			'alma_credit_gateway_nonce_field',
			'alma_credit_gateway_nonce_action'
		);

		// phpcs:ignore
		if ( $_POST['alma_plan_key'] && ! $this->check_values( $_POST['alma_plan_key'],
			array( 'general_6_0_0', 'general_10_0_0', 'general_12_0_0' )
		) ) {
			ShopNotificationHelper::notifyError(
				__(
					'Please choose a valid option.',
					'alma-gateway-for-woocommerce'
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Expose the payment fields to the frontend.
	 *
	 * @return void
	 * @throws GatewayException
	 */
	public function payment_fields() {

		/** @var ConfigService $config_service */
		$config_service = Plugin::get_container()->get( ConfigService::class );

		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );

		$feePlanList = $this->getFeePlanList();

		try {
			$template_helper->getTemplate(
				'gateway-description.php',
				array(
					'alma_woocommerce_gateway_description' => $this->description,
				),
				'partials'
			);

			/** @var FeePlanAdapter $fee_plan_adapter */
			foreach ( $feePlanList as $fee_plan_adapter ) {
				$template_helper->getTemplate(
					'gateway-options.php',
					array(
						'alma_woocommerce_gateway_payment_method' => $this->get_payment_method(),
						'alma_woocommerce_gateway_plan_key'       => $fee_plan_adapter->getPlanKey(),
						'alma_woocommerce_gateway_logo_url'       => AssetsHelper::getImage( 'images/alma_card_logo.svg' ),
						'alma_woocommerce_gateway_fee_plan_label' => $fee_plan_adapter->getLabel(),
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
						'alma_woocommerce_gateway_name'  => $this->get_name(),
						'alma_woocommerce_gateway_fee_plan' => $fee_plan_adapter,
						'alma_woocommerce_gateway_in_page_enabled' => $config_service->isInPageEnabled(),
						'alma_woocommerce_gateway_in_page_iframe_selector' => sprintf(
							'alma_%s_gateway_in_page',
							$this->get_payment_method()
						),
						'alma_woocommerce_gateway_nonce' => $this->form_helper->generateTokenField(
							sprintf( '%s_nonce_action', $this->get_name() ),
							sprintf( '%s_nonce_field', $this->get_name() ),
						),
					),
					'partials'
				);
			}
		} catch ( TemplateHelperException $e ) {
			throw new GatewayException( 'Can not display Gateway form', 0, $e );
		}

		wp_localize_script(
			'alma-in-page',
			'alma_woocommerce_gateway_credit_gateway',
			array(
				'type'         => $this->get_payment_method(),
				'gateway_name' => sprintf( 'alma_%s_gateway', $this->get_payment_method() ),
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
