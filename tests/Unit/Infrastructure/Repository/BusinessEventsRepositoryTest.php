<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Repository;

use Alma\Gateway\Infrastructure\Repository\BusinessEventsRepository;
use PHPUnit\Framework\TestCase;
use stdClass;

class BusinessEventsRepositoryTest extends TestCase
{
	private BusinessEventsRepository $businessEventsRepository;

	public function setUp(): void {
		$this->businessEventsRepository = new BusinessEventsRepository();
	}

	public function testAlreadyExistIfNotExist() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_var', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT COUNT(*) FROM wp_alma_business_events WHERE cart_id = 123');

		$wpdb->expects($this->once())
		     ->method('get_var')
		     ->willReturn('0');

		$this->assertFalse($this->businessEventsRepository->alreadyExist(123));
	}

	public function testAlreadyExistIfExist() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_var', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT COUNT(*) FROM wp_alma_business_events WHERE cart_id = 456');

		$wpdb->expects($this->once())
		     ->method('get_var')
		     ->willReturn('1');

		$this->assertTrue($this->businessEventsRepository->alreadyExist(456));
	}
}