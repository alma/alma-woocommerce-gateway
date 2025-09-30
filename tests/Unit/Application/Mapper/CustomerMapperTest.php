<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\API\Application\DTO\CustomerDto;
use Alma\Gateway\Application\Mapper\CustomerMapper;
use Alma\Gateway\Tests\Unit\Mocks\OrderAdapterMockFactory;
use PHPUnit\Framework\TestCase;

class CustomerMapperTest extends TestCase {

	private $customerMapper;

	protected function setUp(): void {
		$this->customerMapper = new CustomerMapper();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->customerMapper = null;
	}

	public function testBuildCustomerDto(): void {
		$orderInterface = OrderAdapterMockFactory::createMock( $this );
		$customerDto    = $this->customerMapper->buildCustomerDto( $orderInterface );
		$this->assertInstanceOf( CustomerDto::class, $customerDto );
		$this->assertEquals( OrderAdapterMockFactory::resultArray(), $customerDto->toArray() );
	}

}