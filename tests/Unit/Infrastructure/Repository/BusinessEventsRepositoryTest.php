<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Repository;

use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use PHPUnit\Framework\TestCase;
use stdClass;
use wpdb;

class BusinessEventsRepositoryTest extends TestCase
{
	private BusinessEventsRepository $businessEventsRepository;

	public function setUp(): void {
		$this->businessEventsRepository = new BusinessEventsRepository();
	}

	public function testIsCartIdValidNotRowReturned() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_row', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT order_id FROM wp_alma_business_events WHERE cart_id = 123');

		$wpdb->expects($this->once())
		     ->method('get_row')
		     ->willReturn(null);

		$repository = new BusinessEventsRepository();
		$result = $repository->isCartIdValid(123);

		$this->assertFalse($result);
	}

	public function testIsCartIdValidCartExist() {
		global $wpdb;
		$row = new Stdclass();
		$row->order_id = 1;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_row', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT order_id FROM wp_alma_business_events WHERE cart_id = 456');

		$wpdb->expects($this->once())
		     ->method('get_row')
		     ->willReturn($row);

		$repository = new BusinessEventsRepository();
		$result = $repository->isCartIdValid(456);

		$this->assertFalse($result);
	}

	public function testIsCartIdValidCartDoesntExist() {
		global $wpdb;
		$row = new Stdclass();
		$row->order_id = null;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_row', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT order_id FROM wp_alma_business_events WHERE cart_id = 456');

		$wpdb->expects($this->once())
		     ->method('get_row')
		     ->willReturn($row);

		$repository = new BusinessEventsRepository();
		$result = $repository->isCartIdValid(456);

		$this->assertTrue($result);
	}
}