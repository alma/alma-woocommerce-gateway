<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\API\Application\DTO\OrderDto;
use Alma\Gateway\Application\Mapper\OrderMapper;
use Alma\Gateway\Tests\Unit\Mocks\OrderAdapterMockFactory;
use PHPUnit\Framework\TestCase;

class OrderMapperTest extends TestCase {

	private $orderMapper;

	protected function setUp(): void {
		$this->orderMapper = new OrderMapper();
	}

	protected function tearDown(): void {
		$this->orderMapper = null;
	}

	public function testOrderMapper() {
		$orderAdapterMock = OrderAdapterMockFactory::createMock( $this );
		$orderDto         = $this->orderMapper->buildOrderDto( $orderAdapterMock );
		$this->assertInstanceOf(
			OrderDto::class,
			$orderDto
		);
		$this->assertEquals(
			[
				'merchant_reference' => 'ORDER123',
				'merchant_url'       => 'http://example.com/wp-admin/post.php?post=123&action=edit',
				'customer_url'       => 'http://example.com/wp-admin/post.php?post=123&action=view',
				'comment'            => 'Please deliver between 9 AM and 5 PM.',
			],
			$orderDto->toArray()
		);
	}

}
