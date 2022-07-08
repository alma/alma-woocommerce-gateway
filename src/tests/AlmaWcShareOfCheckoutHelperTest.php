<?php

use PHPUnit\Framework\TestCase;

class AlmaWcShareOfCheckoutHelperTest extends TestCase {

	const EUR_CURRENCY_CODE = 'EUR';
	const USD_CURRENCY_CODE = 'USD';

	/**
	 * Call protected/private method of a class.
	 *
	 * @url https://jtreminio.com/blog/unit-testing-tutorial-part-iii-testing-protected-private-methods-coverage-reports-and-crap/
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 * @throws ReflectionException
	 */
	public function invokeMethod( &$object, $methodName, array $parameters = array() )
	{
		$reflection = new \ReflectionClass( get_class($object) );
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}

	/**
	 * Data provider to test "from_date".
	 *
	 * @return string[][]
	 */
	public function data_provider_from_date() {
		return [
			"Normal Date" => [ '2022-07-01', '2022-07-01 00:00:00' ],
			"Past Date"   => [ '2019-01-01', '2019-01-01 00:00:00' ],
			"Futur Date"  => [ '2031-12-31', '2031-12-31 00:00:00' ],
		];
	}

	/**
	 * Test "from_date".
	 *
	 * @dataProvider data_provider_from_date
	 */
	public function test_get_from_date( $from_date, $expected ) {
		$helper = new Alma_WC_Share_Of_Checkout_Helper( new Alma_WC_Helper_Order() );
		$result = $this->invokeMethod( $helper, 'get_from_date', array( $from_date ) );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Data provider to test "to_date".
	 *
	 * @return string[][]
	 */
	public function data_provider_to_date() {
		return [
			"Normal Date" => [ '2022-07-01', '2022-07-01 23:59:59' ],
			"Past Date"   => [ '2019-01-01', '2019-01-01 23:59:59' ],
			"Futur Date"  => [ '2031-12-31', '2031-12-31 23:59:59' ],
		];
	}

	/**
	 * Test "to_date".
	 *
	 * @dataProvider data_provider_to_date
	 */
	public function test_get_to_date( $to_date, $expected ) {
		$helper = new Alma_WC_Share_Of_Checkout_Helper( new Alma_WC_Helper_Order() );
		$result = $this->invokeMethod( $helper, 'get_to_date', array( $to_date ) );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * The provider for the method test_get_payload().
	 *
	 * @return array[]
	 */
	public function ordersGetPayload() {
		return [
			'order get payload' => [
				self::orders_by_date_range_mock(),
				self::expected_payload()
			]
		];
	}

	/**
	 * Orders by date rang used to build payload.
	 *
	 * @return array
	 */
	public function orders_by_date_range_mock() {
		$ordersMock    = [];
		$ordersFactory = [
			[
				'id_currency' => self::EUR_CURRENCY_CODE,
				'total_paid_tax_incl' => 100.00,
				'module' => 'alma',
			],
			[
				'id_currency' => self::USD_CURRENCY_CODE,
				'total_paid_tax_incl' => 200.00,
				'module' => 'paypal',
			],
			[
				'id_currency' => self::EUR_CURRENCY_CODE,
				'total_paid_tax_incl' => 55.00,
				'module' => 'alma',
			],
			[
				'id_currency' => self::USD_CURRENCY_CODE,
				'total_paid_tax_incl' => 100.00,
				'module' => 'alma',
			],
		];

		foreach( $ordersFactory as $orderFactory ) {
			$orderMock = Mockery::mock(WC_Order::class );
			$orderMock->shouldReceive('get_currency' )->andReturn( $orderFactory['id_currency'] );
			$orderMock->shouldReceive('get_total' )->andReturn( $orderFactory['total_paid_tax_incl'] );
			$orderMock->shouldReceive('get_payment_method' )->andReturn( $orderFactory['module'] );
			$ordersMock[] = $orderMock;
		}
		return $ordersMock;
	}

	/**
	 * Expected payload.
	 *
	 * @return array
	 */
	public function expected_payload() {
		return [
			'start_time' => '2022-01-01 00:00:00',
			'end_time' => '2022-01-01 23:59:59',
			'orders' => [
				[
					"total_order_count"=> 2,
					"total_amount"=> 15500,
					"currency"=> "EUR"
				],
				[
					"total_order_count"=> 2,
					"total_amount"=> 30000,
					"currency"=> "USD"
				]
			],
			'payment_methods' => [
				[
					"payment_method_name" => "alma",
					"orders" => [
						[
							"order_count" => 2,
							"amount" => 15500,
							"currency" => "EUR"
						],
						[
							"order_count" => 1,
							"amount" => 10000,
							"currency" => "USD"
						]
					]
				],
				[
					"payment_method_name" => "paypal",
					"orders" => [
						[
							"order_count" => 1,
							"amount" => 20000,
							"currency" => "USD"
						]
					]
				]
			],
		];
	}

	/**
	 * Test get_payload() method from Alma_WC_Share_Of_Checkout_Helper class.
	 *
	 * @dataProvider ordersGetPayload
	 * @return void
	 */
	public function test_get_payload( $orders_by_date_range_mock, $expectedPayload ) {
		$orderHelperMock       = Mockery::mock(Alma_WC_Helper_Order::class );
		$orderHelperMock->shouldReceive('get_orders_by_date_range' )->andReturn( $orders_by_date_range_mock );
		$shareOfCheckoutHelper = new Alma_WC_Share_Of_Checkout_Helper( $orderHelperMock );
		$random_date = '2022-01-01';
		$this->assertEquals( $expectedPayload, $shareOfCheckoutHelper->get_payload( $random_date ) );
	}

}
