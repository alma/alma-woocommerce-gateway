<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\Client\Application\DTO\CustomerDto;
use Alma\Gateway\Application\Mapper\CustomerMapper;
use Alma\Gateway\Tests\Unit\Mocks\OrderAdapterMockTrait;
use PHPUnit\Framework\TestCase;

class CustomerMapperTest extends TestCase {
	use OrderAdapterMockTrait;

	private $customerMapper;

	public function testBuildCustomerDto(): void {
		$orderInterface = $this->createOrderAdapterMock();
		$customerDto    = $this->customerMapper->buildCustomerDto( $orderInterface );
		$this->assertInstanceOf( CustomerDto::class, $customerDto );
		$this->assertEquals( self::orderAdapterResultArray(), $customerDto->toArray() );
	}

	protected function setUp(): void {
		$this->customerMapper = new CustomerMapper();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->customerMapper = null;
	}

}
