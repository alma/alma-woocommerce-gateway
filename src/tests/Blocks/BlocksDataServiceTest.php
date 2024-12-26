<?php

namespace Alma\Woocommerce\Tests\Blocks;

use Alma\API\Client;
use Alma\API\Endpoints\Results\Eligibility;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Blocks\BlocksDataService;
use Alma\Woocommerce\Exceptions\ApiClientException;
use Alma\Woocommerce\Services\AlmaClientService;
use Alma\Woocommerce\WcProxy\FunctionsProxy;
use WP_UnitTestCase;

class BlocksDataServiceTest extends WP_UnitTestCase {

	private $alma_client_service;
	private $alma_logger;
	private $blocks_data_service;
	private $function_proxy;

	public function set_up() {
		$this->alma_client_service = $this->createMock( AlmaClientService::class );
		$this->function_proxy      = $this->createMock( FunctionsProxy::class );
		$this->alma_logger         = $this->createMock( AlmaLogger::class );
		$this->blocks_data_service = new BlocksDataService(
			$this->alma_client_service,
			$this->function_proxy,
			$this->alma_logger
		);
	}

	public function test_get_blocks_with_error_in_client() {
		$this->alma_client_service
			->expects( $this->once() )
			->method( 'get_alma_client' )
			->willThrowException( new ApiClientException( 'Api key not set' ) );
		$this->function_proxy
			->expects( $this->once() )
			->method( 'send_http_error_response' )
			->with(
				[
					'success' => false,
				], 500
			);
		$this->assertNull( $this->blocks_data_service->get_blocks_data() );
	}

	/**
	 * Test blocks data return without error
	 *
	 * @return void
	 */
	public function test_get_blocks_data() {
		$client_mock = $this->createMock( Client::class );
		$this->alma_client_service
			->expects( $this->once() )
			->method( 'get_alma_client' )
			->willReturn( $client_mock );
		$this->alma_client_service
			->expects( $this->once() )
			->method( 'get_eligibility' )
			->with( $client_mock, WC()->cart )
			->willReturn( $this->eligibility_array() );

		$this->function_proxy
			->expects( $this->once() )
			->method( 'send_http_response' )
			->with( $this->response_data() );
		$this->assertNull( $this->blocks_data_service->get_blocks_data() );
	}

	private function response_data() {
		return [
			'success'     => true,
			'eligibility' => [
				'alma_pay_now'    => [],
				'alma'            => [
					'general_3_0_0' => [
						'paymentPlan'             => [
							[
								"due_date"              => 1735208969,
								"total_amount"          => 5773,
								"customer_fee"          => 273,
								"customer_interest"     => 0,
								"purchase_amount"       => 5500,
								"localized_due_date"    => "today",
								"time_delta_from_start" => null
							],
							[
								"due_date"              => 1737887369,
								"total_amount"          => 5500,
								"customer_fee"          => 0,
								"customer_interest"     => 0,
								"purchase_amount"       => 5500,
								"localized_due_date"    => "January 26, 2025",
								"time_delta_from_start" => null
							],
							[
								"due_date"              => 1740565769,
								"total_amount"          => 5500,
								"customer_fee"          => 0,
								"customer_interest"     => 0,
								"purchase_amount"       => 5500,
								"localized_due_date"    => "February 26, 2025",
								"time_delta_from_start" => null
							]
						],
						"customerTotalCostAmount" => 273
					]
				],
				'alma_pay_later'  => [
					'general_1_15_0' => [
						'paymentPlan'             => [
							[
								"due_date"              => 1736504969,
								"total_amount"          => 16500,
								"customer_fee"          => 0,
								"customer_interest"     => 0,
								"purchase_amount"       => 16500,
								"localized_due_date"    => "January 10, 2025",
								"time_delta_from_start" => null
							],
						],
						"customerTotalCostAmount" => 0
					]
				],
				'alma_pnx_plus_4' => []
			],
			'cart_total'  => (float) WC()->cart->get_total( '' )
		];
	}

	private function eligibility_array() {
		$eligibilities = json_decode
		(
			'{
				  "general_1_15_0":{
				    "is_eligible":true,
				    "reasons":null,
				    "constraints":null,
				    "payment_plan":[
				      {
				        "due_date":1736504969,
				        "total_amount":16500,
				        "customer_fee":0,
				        "customer_interest":0,
				        "purchase_amount":16500,
				        "localized_due_date":"January 10, 2025",
				        "time_delta_from_start":null
				      }
				    ],
				    "installments_count":1,
				    "deferred_days":15,
				    "deferred_months":0,
				    "customer_total_cost_amount":0,
				    "customer_total_cost_bps":0,
				    "annual_interest_rate":0
				  },
				  "general_3_0_0":{
				    "is_eligible":true,
				    "reasons":null,
				    "constraints":null,
				    "payment_plan":[
				      {
				        "due_date":1735208969,
				        "total_amount":5773,
				        "customer_fee":273,
				        "customer_interest":0,
				        "purchase_amount":5500,
				        "localized_due_date":"today",
				        "time_delta_from_start":null
				      },
				      {
				        "due_date":1737887369,
				        "total_amount":5500,
				        "customer_fee":0,
				        "customer_interest":0,
				        "purchase_amount":5500,
				        "localized_due_date":"January 26, 2025",
				        "time_delta_from_start":null
				      },
				      {
				        "due_date":1740565769,
				        "total_amount":5500,
				        "customer_fee":0,
				        "customer_interest":0,
				        "purchase_amount":5500,
				        "localized_due_date":"February 26, 2025",
				        "time_delta_from_start":null
				      }
				    ],
				    "installments_count":3,
				    "deferred_days":0,
				    "deferred_months":0,
				    "customer_total_cost_amount":273,
				    "customer_total_cost_bps":165,
				    "annual_interest_rate":2230
				  }
			}'
			, true );
		$result        = [];
		foreach ( $eligibilities as $eligibilityData ) {
			$eligibility                          = new Eligibility( $eligibilityData );
			$result[ $eligibility->getPlanKey() ] = $eligibility;
		}

		return $result;
	}

}