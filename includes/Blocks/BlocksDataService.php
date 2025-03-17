<?php
/**
 * BlocksDataService.
 *
 * @since 5.8.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Services
 * @namespace Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Blocks;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Exceptions\ApiClientException;
use Alma\Woocommerce\Gateways\Inpage\InPageGateway;
use Alma\Woocommerce\Gateways\Inpage\PayLaterGateway as InPagePayLaterGateway;
use Alma\Woocommerce\Gateways\Inpage\PayMoreThanFourGateway as InPagePayMoreThanFourGateway;
use Alma\Woocommerce\Gateways\Inpage\PayNowGateway as InPagePayNowGateway;
use Alma\Woocommerce\Gateways\Standard\PayLaterGateway;
use Alma\Woocommerce\Gateways\Standard\PayMoreThanFourGateway;
use Alma\Woocommerce\Gateways\Standard\PayNowGateway;
use Alma\Woocommerce\Gateways\Standard\StandardGateway;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Services\AlmaClientService;
use Alma\Woocommerce\WcProxy\FunctionsProxy;

class BlocksDataService {

	const WEBHOOK_PATH = 'alma_blocks_data';

	/**
	 * @var AlmaClientService
	 */
	private $alma_client_service;
	/**
	 * @var AlmaLogger
	 */
	private $logger;
	/**
	 * @var FunctionsProxy
	 */
	private $function_proxy;

	/**
	 * Block data service - Webhook for blocks data
	 *
	 * @param $alma_client_service
	 * @param $function_proxy
	 * @param $logger
	 */
	public function __construct(
		$alma_client_service = null,
		$function_proxy = null,
		$logger = null
	) {
		$this->alma_client_service = $this->init_alma_client_service( $alma_client_service );
		$this->function_proxy      = $this->init_function_proxy( $function_proxy );
		$this->logger              = $this->init_alma_logger( $logger );
	}

	/**
	 * Init alma client service
	 *
	 * @param AlmaClientService|null $alma_client_service
	 *
	 * @return AlmaClientService
	 */
	private function init_alma_client_service( $alma_client_service ) {
		if ( ! isset( $alma_client_service ) ) {
			$alma_client_service = new AlmaClientService();
		}

		return $alma_client_service;
	}

	/**
	 * Init function proxy
	 *
	 * @param FunctionsProxy|null $function_proxy
	 *
	 * @return FunctionsProxy
	 */
	private function init_function_proxy( $function_proxy ) {
		if ( ! isset( $function_proxy ) ) {
			$function_proxy = new FunctionsProxy();
		}

		return $function_proxy;
	}

	/**
	 * Init Alma Logger
	 *
	 * @param AlmaLogger|null $logger
	 *
	 * @return AlmaLogger
	 */
	private function init_alma_logger( $logger ) {
		if ( ! isset( $logger ) ) {
			$logger = new AlmaLogger();
		}

		return $logger;
	}

	/**
	 * Init webhook alma_blocks_data
	 * No test need a proxy to test
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action(
			ToolsHelper::action_for_webhook( self::WEBHOOK_PATH ),
			array(
				$this,
				'get_blocks_data',
			)
		);
	}

	/**
	 * Send HTTP Response to AJAX call for eligibility in checkout page
	 *
	 * @return void
	 */
	public function get_blocks_data() {
		try {
			$alma_client   = $this->alma_client_service->get_alma_client();
			$eligibilities = $this->alma_client_service->get_eligibility( $alma_client, WC()->cart );

		} catch ( ApiClientException $e ) {
			$this->logger->info( 'Impossible to set Alma client: ' . $e->getMessage() );
			$this->function_proxy->send_http_error_response(
				array(
					'success' => false,
				),
				500
			);

			// wp_send_json_error make die but return is used on test
			return;
		}

		$response = array(
			'success'     => true,
			'eligibility' => $this->format_eligibility_for_blocks( $eligibilities ),
			'cart_total'  => (float) WC()->cart->get_total( '' ),
		);

		// Send JSON response
		$this->function_proxy->send_http_response( $response );
	}

	/**
	 * Format eligibility with gateway for frontend blocks
	 *
	 * @param $eligibilities
	 *
	 * @return array|array[]
	 */
	public function format_eligibility_for_blocks( $eligibilities ) {
		$in_page_activated = $this->alma_client_service->in_page_is_activated();
		$gateways_keys     = array(
			'redirect' => array(
				'pay_now'      => PayNowGateway::GATEWAY_ID,
				'installments' => StandardGateway::GATEWAY_ID,
				'pay_later'    => PayLaterGateway::GATEWAY_ID,
				'credit'       => PayMoreThanFourGateway::GATEWAY_ID,
			),
			'in_page'  => array(
				'pay_now'      => InPagePayNowGateway::GATEWAY_ID,
				'installments' => InPageGateway::GATEWAY_ID,
				'pay_later'    => InPagePayLaterGateway::GATEWAY_ID,
				'credit'       => InPagePayMoreThanFourGateway::GATEWAY_ID,
			),
		);
		$gateway_mode      = $in_page_activated ? 'in_page' : 'redirect';
		$gateways          = array(
			$gateways_keys[ $gateway_mode ]['pay_now']   => array(),
			$gateways_keys[ $gateway_mode ]['installments'] => array(),
			$gateways_keys[ $gateway_mode ]['pay_later'] => array(),
			$gateways_keys[ $gateway_mode ]['credit']    => array(),
		);
		foreach ( $eligibilities as $plan_key => $eligibility ) {

			/** @var Eligibility $eligibility */
			if ( ! $eligibility->isEligible() ) {
				continue;
			}
			$installment_count = $eligibility->getInstallmentsCount();
			$deferred_days     = $eligibility->getDeferredDays();
			$deferred_months   = $eligibility->getDeferredMonths();

			// Pay now
			if ( 1 === $installment_count && 0 === $deferred_months && 0 === $deferred_days ) {
				$gateways[ $gateways_keys[ $gateway_mode ]['pay_now'] ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay in installments
			if ( 1 < $installment_count && 4 >= $installment_count && 0 === $deferred_months && 0 === $deferred_days ) {
				$gateways[ $gateways_keys[ $gateway_mode ]['installments'] ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay in credit
			if ( 4 < $installment_count && 0 === $deferred_months && 0 === $deferred_days ) {
				$gateways[ $gateways_keys[ $gateway_mode ]['credit'] ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay later
			if ( 1 === $installment_count && ( 0 < $deferred_months || 0 < $deferred_days ) ) {
				$gateways[ $gateways_keys[ $gateway_mode ]['pay_later'] ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
		}

		return $gateways;
	}

	/**
	 * @param Eligibility $eligibility
	 *
	 * @return array
	 */
	private function format_plan_content_for_blocks( $eligibility ) {
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
