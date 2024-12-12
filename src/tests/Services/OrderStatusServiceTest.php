<?php

namespace Alma\Woocommerce\Tests\Services;

use Alma\API\Client;
use Alma\API\DependenciesError;
use Alma\API\Endpoints\Payments;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Exceptions\NoOrderException;
use Alma\Woocommerce\Exceptions\RequirementsException;
use Alma\Woocommerce\Services\OrderStatusService;
use Alma\Woocommerce\WcProxy\OrderProxy;
use WP_UnitTestCase;

class OrderStatusServiceTest extends WP_UnitTestCase
{
	const ORDER_ID = 42;
	/**
	 * @var AlmaLogger
	 */
	private $alma_logger_mock;
	/**
	 * @var AlmaSettings
	 */
	private $alma_settings_mock;
	/**
	 * @var OrderStatusService
	 */
	private $order_status_service;
	/**
	 * @var Client
	 */
	private $alma_client_mock;

	/**
	 * @var OrderProxy
	 */
	private $order_proxy_mock;
	/**
	 * @var \WC_Order
	 */
	private $order_mock;

	public function set_up()
	{
		$this->alma_logger_mock = $this->createMock(AlmaLogger::class);
		$this->alma_settings_mock = $this->createMock(AlmaSettings::class);
		$this->alma_client_mock = $this->createMock(Client::class);
		$this->alma_client_mock->payments = $this->createMock(Payments::class);
		$this->alma_settings_mock->alma_client = $this->alma_client_mock;
		$this->order_proxy_mock = $this->createMock(OrderProxy::class);
		$this->order_mock = $this->createMock(\WC_Order::class);
		$this->order_status_service = new OrderStatusService(
			$this->alma_settings_mock,
			$this->order_proxy_mock,
			$this->alma_logger_mock
		);
	}

	public function test_no_order_for_event()
	{
		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_by_id')
			->with(self::ORDER_ID)
			->willThrowException(new NoOrderException(self::ORDER_ID));

		$this->order_proxy_mock
			->expects($this->never())
			->method('get_display_order_reference');

		$this->alma_client_mock->payments
			->expects($this->never())
			->method('addOrderStatusByMerchantOrderReference');

		$this->assertNull(
			$this->order_status_service->send_order_status(
				self::ORDER_ID,
				'old_status',
				'new_status'
			)
		);
	}

	public function test_not_an_alma_order()
	{
		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_by_id')
			->with(self::ORDER_ID)
			->willReturn($this->order_mock);

		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_payment_method')
			->with($this->order_mock)
			->willReturn('paypal');

		$this->order_proxy_mock
			->expects($this->never())
			->method('get_display_order_reference');

		$this->alma_client_mock->payments
			->expects($this->never())
			->method('addOrderStatusByMerchantOrderReference');

		$this->assertNull(
			$this->order_status_service->send_order_status(
				self::ORDER_ID,
				'old_status',
				'new_status'
			)
		);
	}

	public function test_alma_order_error_in_init()
	{
		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_by_id')
			->with(self::ORDER_ID)
			->willReturn($this->order_mock);

		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_payment_method')
			->with($this->order_mock)
			->willReturn('alma');

		$this->alma_settings_mock
			->method('get_alma_client')
			->willThrowException(new DependenciesError('A dependency error'));

		$this->order_proxy_mock
			->expects($this->never())
			->method('get_display_order_reference');

		$this->alma_client_mock->payments
			->expects($this->never())
			->method('addOrderStatusByMerchantOrderReference');

		$this->assertNull(
			$this->order_status_service->send_order_status(
				self::ORDER_ID,
				'old_status',
				'new_status'
			)
		);
	}

	public function test_no_alma_payment_id_in_order()
	{
		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_by_id')
			->with(self::ORDER_ID)
			->willReturn($this->order_mock);

		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_payment_method')
			->with($this->order_mock)
			->willReturn('alma');

		$this->order_proxy_mock
			->method('get_alma_payment_id')
			->willThrowException(new RequirementsException('No payment id for this order'));

		$this->alma_client_mock->payments
			->expects($this->never())
			->method('addOrderStatusByMerchantOrderReference');

		$this->assertNull(
			$this->order_status_service->send_order_status(
				self::ORDER_ID,
				'old_status',
				'new_status'
			)
		);
	}

	/**
	 * @dataProvider order_status_is_shipped_data_provider
	 * @param string $status
	 * @param bool | null $is_shipped
	 * @param string $method
	 * @return void
	 * @throws NoOrderException
	 */
	public function test_send_order_status($status, $is_shipped, $method)
	{
		$alma_payment_id = 'payment_12435';
		$wc_order_number = 'ref12345';
		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_by_id')
			->with(self::ORDER_ID)
			->willReturn($this->order_mock);

		$this->order_proxy_mock
			->expects($this->once())
			->method('get_order_payment_method')
			->with($this->order_mock)
			->willReturn($method);

		$this->order_proxy_mock
			->expects($this->once())
			->method('get_alma_payment_id')
			->with($this->order_mock)
			->willReturn($alma_payment_id);

		$this->order_proxy_mock
			->expects($this->once())
			->method('get_display_order_reference')
			->with($this->order_mock)
			->willReturn($wc_order_number);

		$this->alma_settings_mock
			->expects($this->once())
			->method('get_alma_client');

		$this->alma_client_mock->payments
			->expects($this->once())
			->method('addOrderStatusByMerchantOrderReference')
			->with(
				$alma_payment_id,
				$wc_order_number,
				$status,
				$this->callback(function ($value) use ($is_shipped) {
					return $value === $is_shipped;
				})
			);

		$this->assertNull(
			$this->order_status_service->send_order_status(
				self::ORDER_ID,
				'old_status',
				$status
			)
		);
	}

	public function order_status_is_shipped_data_provider()
	{
		return [
			"with pending payment status" => [
				"status" => "pending",
				"is_shipped" => false,
				"method" => "alma"
			],
			"with on hold status" => [
				"status" => "on-hold",
				"is_shipped" => false,
				"method" => "alma_in_page"
			],
			"with Processing status" => [
				"status" => "processing",
				"is_shipped" => false,
				"method" => "alma"
			],
			"with Completed status" => [
				"status" => "completed",
				"is_shipped" => true,
				"method" => "alma_in_page"
			],
			"with Failed status" => [
				"status" => "failed",
				"is_shipped" => false,
				"method" => "alma"
			],
			"with Refunded status" => [
				"status" => "refunded",
				"is_shipped" => false,
				"method" => "alma"
			],
			"with Checkout Draft status" => [
				"status" => "checkout-draft",
				"is_shipped" => false,
				"method" => "alma"
			],
			"with an unknown status" => [
				"status" => "other",
				"is_shipped" => null,
				"method" => "alma"
			],
		];
	}

}
