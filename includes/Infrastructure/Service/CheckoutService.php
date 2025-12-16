<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;
use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Block\Gateway\AbstractGatewayBlock;
use Alma\Gateway\Infrastructure\Exception\CheckoutServiceException;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Gateway\Frontend\AbstractFrontendGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\SecurityHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;
use Alma\Gateway\Plugin;

class CheckoutService {

	protected AbstractFrontendGateway $gateway;
	private ConfigService $configService;
	private FeePlanRepository $feePlanRepository;
	private SecurityHelper $securityHelper;


	public function __construct(
		ConfigService $configService,
		FeePlanRepository $feePlanRepository,
		SecurityHelper $securityHelper
	) {
		$this->configService     = $configService;
		$this->feePlanRepository = $feePlanRepository;
		$this->securityHelper    = $securityHelper;

		$this->initialize();
	}

	/**
	 * Initialize AJAX handlers for Checkout.
	 */
	public function initialize() {
		add_action(
			'woocommerce_api_alma_checkout_data',
			array(
				$this,
				'getCheckoutData',
			)
		);
	}

	/**
	 * AJAX handler to get Checkout data.
	 * Can receive billing_country and shipping_country as GET parameters
	 * to filter on Eligibility.
	 * @throws CheckoutServiceException
	 */
	public function getCheckoutData(): void {

		/** @var GatewayRepository $gatewayRepository */
		$gatewayRepository = Plugin::get_container()->get( GatewayRepository::class );
		wp_send_json( $this->getCheckoutParams( $gatewayRepository->findAllAlmaGatewayBlocks() ) );
	}

	/**
	 * Get Checkout parameters
	 * @throws CheckoutServiceException
	 */
	public function getCheckoutParams( array $almaGatewayBlocks ): array {

		$isInPage = $this->configService->isInPageEnabled();
		try {
			$feePlanListAdapter = $this->feePlanRepository->getAllWithEligibility( ContextHelper::getCart()->getCartTotal(),
				true )->filterEnabled();
		} catch ( FeePlanRepositoryException $e ) {
			throw new CheckoutServiceException( $e->getMessage(), 0, $e );
		}


		// Prepare response
		$nonce_key = sprintf( '%s_nonce_field', 'alma_checkout' );
		$params    = array(
			'success'          => true,
			'is_in_page'       => $isInPage,
			'gateway_settings' => array_merge_recursive(
				$this->formatBlocksForCheckout( $almaGatewayBlocks ),
				$this->formatEligibilityForCheckout( $feePlanListAdapter ),
			),
			'nonce_key'        => $nonce_key,
			'nonce_value'      => $this->securityHelper->generateToken( $nonce_key ),
		);
		if ( $isInPage ) {
			$params['merchant_id'] = $this->configService->getMerchantId();
			$params['environment'] = strtoupper( $this->configService->getEnvironment()->getMode() );
			$params['language']    = ContextHelper::getLanguage();
		}

		return $params;
	}

	/**
	 * Format Fee Plans for block
	 * @todo use a DTO
	 */
	public function formatEligibilityForCheckout( FeePlanListAdapter $feePlanListAdapter ): array {

		$gateways = array(
			'alma_paynow_gateway'   => array(),
			'alma_pnx_gateway'      => array(),
			'alma_paylater_gateway' => array(),
			'alma_credit_gateway'   => array(),
		);

		foreach ( $feePlanListAdapter as $feePlanAdapter ) {

			// Pay now
			if ( $feePlanAdapter->isPayNow() ) {
				$gateways['alma_paynow_gateway']['fee_plans_settings'][ $feePlanAdapter->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $feePlanAdapter );
			}
			// Pay in installments
			if ( $feePlanAdapter->isPnXOnly() ) {
				$gateways['alma_pnx_gateway']['fee_plans_settings'][ $feePlanAdapter->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $feePlanAdapter );
			}
			// Pay in credit
			if ( $feePlanAdapter->isCredit() ) {
				$gateways['alma_credit_gateway']['fee_plans_settings'][ $feePlanAdapter->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $feePlanAdapter );
			}
			// Pay later
			if ( $feePlanAdapter->isPayLaterOnly() ) {
				$gateways['alma_paylater_gateway']['fee_plans_settings'][ $feePlanAdapter->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $feePlanAdapter );
			}
		}

		return $gateways;
	}

	private function formatBlocksForCheckout( array $almaGatewayBlocks ): array {
		$blocks = [];
		/** @var AbstractGatewayBlock $almaGatewayBlock */
		foreach ( $almaGatewayBlocks as $almaGatewayBlock ) {
			$blocks[ $almaGatewayBlock->get_gateway()->get_name() ] = $this->formatBlocksContentForCheckout( $almaGatewayBlock );
		}

		return $blocks;
	}

	private function formatBlocksContentForCheckout( AbstractGatewayBlock $almaGatewayBlock ): array {
		return array(
			'name'         => $almaGatewayBlock->get_name(),
			'gateway_name' => $almaGatewayBlock->get_gateway()->get_name(),
			'title'        => $almaGatewayBlock->get_gateway()->get_title(),
			'description'  => $almaGatewayBlock->get_gateway()->get_description(),
			'is_pay_now'   => $almaGatewayBlock->get_gateway()->is_pay_now(),
			'is_pay_later' => $almaGatewayBlock->get_gateway()->is_pay_later(),
			'label_button' => L10nHelper::__( 'Pay With Alma' ),
		);
	}

	/**
	 * @param FeePlanAdapter $feePlanAdapter
	 *
	 * @return array
	 */
	private function formatEligibilityContentForCheckout( FeePlanAdapter $feePlanAdapter ): array {
		return array(
			'planKey'                 => $feePlanAdapter->getPlanKey(),
			'paymentPlan'             => $feePlanAdapter->getPaymentPlan(),
			'customerTotalCostAmount' => $feePlanAdapter->getCustomerTotalCostAmount(),
			'installmentsCount'       => $feePlanAdapter->getInstallmentsCount(),
			'deferredDays'            => $feePlanAdapter->getDeferredDays(),
			'deferredMonths'          => $feePlanAdapter->getDeferredMonths(),
			'annualInterestRate'      => $feePlanAdapter->getAnnualInterestRate(),
		);
	}
}
