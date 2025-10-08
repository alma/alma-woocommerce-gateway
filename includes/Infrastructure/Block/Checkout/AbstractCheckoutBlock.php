<?php
/**
 * AlmaBlock.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Blocks
 * @namespace Alma\Woocommerce\Blocks;
 */

namespace Alma\Gateway\Infrastructure\Block\Checkout;

use Alma\API\Domain\Entity\Eligibility;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Exception\AssetsServiceException;
use Alma\Gateway\Infrastructure\Exception\Block\CheckoutBlockException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\Frontend\AbstractFrontendGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\FormHelper;
use Alma\Gateway\Infrastructure\Service\AssetsService;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_Blocks
 */
abstract class AbstractCheckoutBlock extends AbstractPaymentMethodType {

	protected AbstractFrontendGateway $gateway;
	private ConfigService $config_service;
	private FormHelper $form_helper;
	private CartAdapter $cart_adapter;
	private EligibilityProvider $eligibility_provider;
	private AssetsService $assets_service;

	public function __construct( ConfigService $config_service, EligibilityProvider $eligibility_provider, CartAdapter $cart_adapter, FormHelper $form_helper, AssetsService $assets_service ) {

		$this->config_service       = $config_service;
		$this->eligibility_provider = $eligibility_provider;
		$this->cart_adapter         = $cart_adapter;
		$this->form_helper          = $form_helper;
		$this->assets_service       = $assets_service;

		$this->initialize();
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		add_action(
			'woocommerce_api_alma_blocks_data',
			array(
				$this,
				'get_blocks_data',
			)
		);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 * @throws FeePlanRepositoryException
	 */
	public function is_active(): bool {
		return $this->config_service->isBlocksEnabled() && $this->gateway->is_enabled() && $this->gateway->is_available();
	}

	/**
	 * Register the script.
	 *
	 * @return string[]
	 * @throws CheckoutBlockException
	 */
	public function get_payment_method_script_handles(): array {

		// Passer la base URL au JavaScript
		try {
			$params = array(
				'url'              => ContextHelper::getWebhookUrl( 'alma_blocks_data' ),
				'init_eligibility' => $this->format_eligibility_for_blocks(),
				'cart_total'       => $this->cart_adapter->getCartTotal(),
			);
		} catch ( EligibilityServiceException | FeePlanRepositoryException $e ) {
			throw new CheckoutBlockException( 'Unable to get eligibility for blocks', 0, $e );
		}

		if ( false ) { // @todo $this->configService->isInPageEnabled();
			$inPageParams = array(
				'ajax_url' => ContextHelper::getAdminUrl( 'admin-ajax.php' ),
			);
			$params       = array_merge( $inPageParams, $params );
		}

		try {
			$this->assets_service->loadCheckoutBlockAssets( $params );
		} catch ( AssetsServiceException $e ) {
			throw new CheckoutBlockException( 'Unable to load block assets', 0, $e );
		}

		return array( 'alma-blocks-integration' );
	}

	public function get_blocks_data(): void {
		wp_send_json(
			array(
				'success'     => true,
				'eligibility' => $this->format_eligibility_for_blocks(),
				'cart_total'  => $this->cart_adapter->getCartTotal(),
			)
		);
	}

	/**
	 * Format Fee Plans for blocks
	 */
	public function format_eligibility_for_blocks(): array {

		$gateways = array(
			'alma_checkout_paynow_block'   => array(),
			'alma_checkout_pnx_block'      => array(),
			'alma_checkout_paylater_block' => array(),
			'alma_checkout_credit_block'   => array(),
		);

		try {
			$eligibility_list = $this->eligibility_provider->getEligibilityList();
		} catch ( EligibilityServiceException $e ) {
			return $gateways;
		}

		foreach ( $eligibility_list as $eligibility ) {

			// Pay now
			if ( $eligibility->isPayNow() ) {
				$gateways['alma_checkout_paynow_block'][ $eligibility->getPlanKey() ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay in installments
			if ( $eligibility->isPnXOnly() ) {
				$gateways['alma_checkout_pnx_block'][ $eligibility->getPlanKey() ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay in credit
			if ( $eligibility->isCredit() ) {
				$gateways['alma_checkout_credit_block'][ $eligibility->getPlanKey() ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay later
			if ( $eligibility->isPayLaterOnly() ) {
				$gateways['alma_checkout_paylater_block'][ $eligibility->getPlanKey() ] = $this->format_plan_content_for_blocks( $eligibility );
			}
		}

		return $gateways;
	}

	/**
	 * Send data to the js.
	 *
	 * @return array
	 */
	public function get_payment_method_data(): array {

		$is_in_page = false;// @todo $this->configService->isInPageEnabled();

		$data = array(
			'title'        => $this->gateway->get_title(),
			'description'  => $this->gateway->get_description(),
			'gateway_name' => $this->gateway->get_name(),
			'nonce_value'  => $this->form_helper->generateTokenField(
				sprintf( '%s_nonce_action', $this->gateway->get_name() ),
				sprintf( '%s_nonce_field', $this->gateway->get_name() ),
			),
			'label_button' => L10nHelper::__( 'Pay With Alma', 'alma-gateway-for-woocommerce' ),
			'is_in_page'   => $is_in_page,
		);

		if ( $is_in_page ) {
			$data['merchant_id'] = $this->config_service->getMerchantId();
			$data['environment'] = strtoupper( $this->config_service->getEnvironment() );
			$data['language']    = ContextHelper::getLanguage();
		}

		return $data;
	}

	/**
	 * @param Eligibility $eligibility
	 *
	 * @return array
	 */
	private function format_plan_content_for_blocks( Eligibility $eligibility ): array {
		return array(
			'planKey'                 => $eligibility->getPlanKey(),
			'paymentPlan'             => $eligibility->getPaymentPlan(),
			'customerTotalCostAmount' => $eligibility->getCustomerTotalCostAmount(),
			'installmentsCount'       => $eligibility->getInstallmentsCount(),
			'deferredDays'            => $eligibility->getDeferredDays(),
			'deferredMonths'          => $eligibility->getDeferredMonths(),
			'annualInterestRate'      => $eligibility->getAnnualInterestRate(),
		);
	}
}
