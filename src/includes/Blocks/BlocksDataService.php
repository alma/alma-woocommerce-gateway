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
use Alma\Woocommerce\Gateways\Standard\PayMoreThanFourGateway;
use Alma\Woocommerce\Gateways\Standard\PayLaterGateway;
use Alma\Woocommerce\Gateways\Standard\PayNowGateway;
use Alma\Woocommerce\Gateways\Standard\StandardGateway;
use Alma\Woocommerce\Helpers\ToolsHelper;
use Alma\Woocommerce\Services\AlmaClientService;
use Alma\Woocommerce\WcProxy\FunctionsProxy;

class BlocksDataService {
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
	 * Init webhook alma_blocks_data
	 * No test need a proxy to test
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action(
			ToolsHelper::action_for_webhook( AlmaBlock::WEBHOOK_PATH ),
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
			$almaClient    = $this->alma_client_service->get_alma_client();
			$eligibilities = $this->alma_client_service->get_eligibility( WC()->cart, $almaClient );
		} catch ( ApiClientException $e ) {
			$this->logger->info( 'Impossible to set Alma client: ' . $e->getMessage() );
			$this->function_proxy->send_http_error_response( [
				'success' => false,
			], 500 );

			// wp_send_json_error make die but return is used on test
			return;
		}

		$response = [
			'success'     => true,
			'eligibility' => $this->format_eligibility_for_blocks( $eligibilities ),
			'cart_total'  => [ 'cart_total' => (float) WC()->cart->get_total( '' ) ],
		];
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
	private function format_eligibility_for_blocks( $eligibilities ) {
		$gateways = [
			PayNowGateway::GATEWAY_ID          => [],
			StandardGateway::GATEWAY_ID        => [],
			PayLaterGateway::GATEWAY_ID        => [],
			PayMoreThanFourGateway::GATEWAY_ID => []
		];
		foreach ( $eligibilities as $plan_key => $eligibility ) {
			/** @var Eligibility $eligibility */
			$installment_count = $eligibility->getInstallmentsCount();
			$deferred_days     = $eligibility->getDeferredDays();
			$deferred_months   = $eligibility->getDeferredMonths();

			// Pay now
			if ( $installment_count === 1 && $deferred_months === 0 && $deferred_days === 0 ) {
				$gateways[ PayNowGateway::GATEWAY_ID ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay in installments
			if ( $installment_count >= 1 && $installment_count <= 4 && $deferred_months === 0 && $deferred_days === 0 ) {
				$gateways[ StandardGateway::GATEWAY_ID ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay in credit
			if ( $installment_count > 4 && $deferred_months === 0 && $deferred_days === 0 ) {
				$gateways[ PayMoreThanFourGateway::GATEWAY_ID ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
			}
			// Pay later
			if ( $installment_count === 1 && ( $deferred_months > 0 || $deferred_days > 0 ) ) {
				$gateways[ PayLaterGateway::GATEWAY_ID ][ $plan_key ] = $this->format_plan_content_for_blocks( $eligibility );
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
		return [
			'paymentPlan'             => $eligibility->getPaymentPlan(),
			'customerTotalCostAmount' => $eligibility->getCustomerTotalCostAmount(),
		];
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

}