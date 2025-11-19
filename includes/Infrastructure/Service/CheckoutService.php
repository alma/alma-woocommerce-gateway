<?php

namespace Alma\Gateway\Infrastructure\Service;

use Alma\API\Domain\Entity\Eligibility;
use Alma\API\Domain\Entity\EligibilityList;
use Alma\Gateway\Application\Exception\Service\API\EligibilityServiceException;
use Alma\Gateway\Application\Helper\L10nHelper;
use Alma\Gateway\Application\Mapper\EligibilityMapper;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Block\Gateway\AbstractGatewayBlock;
use Alma\Gateway\Infrastructure\Gateway\Frontend\AbstractFrontendGateway;
use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Alma\Gateway\Infrastructure\Helper\SecurityHelper;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Alma\Gateway\Infrastructure\Repository\GatewayRepository;

class CheckoutService {

	protected AbstractFrontendGateway $gateway;
	private ConfigService $configService;
	private GatewayRepository $gatewayRepository;
	private FeePlanRepository $feePlanRepository;
	private EligibilityProvider $eligibilityProvider;
	private SecurityHelper $securityHelper;


	public function __construct(
		ConfigService $configService,
		GatewayRepository $gatewayRepository,
		FeePlanRepository $feePlanRepository,
		EligibilityProvider $eligibilityProvider,
		SecurityHelper $securityHelper
	) {
		$this->configService       = $configService;
		$this->gatewayRepository   = $gatewayRepository;
		$this->feePlanRepository   = $feePlanRepository;
		$this->eligibilityProvider = $eligibilityProvider;
		$this->securityHelper      = $securityHelper;

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
	 */
	public function getCheckoutData(): void {

		wp_send_json( $this->getCheckoutParams() );
	}

	public function getCheckoutParams(): array {

		$isInPage       = $this->configService->isInPageEnabled();
		$eligibilityDto = ( new EligibilityMapper() )
			->buildEligibilityDto(
				ContextHelper::getCart(),
				ContextHelper::getCustomer(),
				$this->feePlanRepository->getAll()->filterEnabled()
			);
		try {
			$eligibilityList = $this->eligibilityProvider->getEligibilityList( $eligibilityDto );
		} catch ( EligibilityServiceException $e ) {
			$eligibilityList = new EligibilityList();
		}

		// Prepare response
		$nonce_key = sprintf( '%s_nonce_field', 'alma_checkout' );
		$params    = array(
			'success'          => true,
			'is_in_page'       => $isInPage,
			'gateway_settings' => array_merge_recursive(
				$this->formatBlocksForCheckout( $this->gatewayRepository->findAllAlmaGatewayBlocks() ),
				$this->formatEligibilityForCheckout( $eligibilityList ),
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
	public function formatEligibilityForCheckout( EligibilityList $eligibilityList ): array {

		$gateways = array(
			'alma_paynow_gateway'   => array(),
			'alma_pnx_gateway'      => array(),
			'alma_paylater_gateway' => array(),
			'alma_credit_gateway'   => array(),
		);

		foreach ( $eligibilityList as $eligibility ) {

			// Pay now
			if ( $eligibility->isPayNow() ) {
				$gateways['alma_paynow_gateway']['fee_plans_settings'][ $eligibility->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $eligibility );
			}
			// Pay in installments
			if ( $eligibility->isPnXOnly() ) {
				$gateways['alma_pnx_gateway']['fee_plans_settings'][ $eligibility->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $eligibility );
			}
			// Pay in credit
			if ( $eligibility->isCredit() ) {
				$gateways['alma_credit_gateway']['fee_plans_settings'][ $eligibility->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $eligibility );
			}
			// Pay later
			if ( $eligibility->isPayLaterOnly() ) {
				$gateways['alma_paylater_gateway']['fee_plans_settings'][ $eligibility->getPlanKey() ] = $this->formatEligibilityContentForCheckout( $eligibility );
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
	 * @param Eligibility $eligibility
	 *
	 * @return array
	 */
	private function formatEligibilityContentForCheckout( Eligibility $eligibility ): array {
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
