<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Repository;

use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use PHPUnit\Framework\TestCase;
use wpdb;

class BusinessEventsRepositoryTest extends TestCase
{
	private BusinessEventsRepository $businessEventsRepository;

	public function setUp(): void {
		$this->businessEventsRepository = new BusinessEventsRepository();
	}

	public function testIsCartIdValidNotRowInDb() {
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
}