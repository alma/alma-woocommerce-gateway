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

	public function testAlreadyConvertedIfCartNotConverted() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_var', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT order_id FROM wp_alma_business_events WHERE cart_id = 123');

		$wpdb->expects($this->once())
		     ->method('get_var')
		     ->willReturn(null);

		$this->assertFalse($this->businessEventsRepository->alreadyConverted(123));
	}

	public function testAlreadyConvertedIfCartConverted() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['get_var', 'prepare'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('prepare')
		     ->willReturn('SELECT order_id FROM wp_alma_business_events WHERE cart_id = 123');

		$wpdb->expects($this->once())
		     ->method('get_var')
		     ->willReturn(42);

		$this->assertTrue($this->businessEventsRepository->alreadyConverted(123));
	}

	public function testSaveEligibility() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['update'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('update')
		     ->with(
			     'wp_alma_business_data',
			     ['is_bnpl_eligible' => 1],
			     ['cart_id' => 789],
			     ['%d'],
			     ['%d']
		     );

		$repository = new BusinessEventsRepository();
		$repository->saveEligibility(789, true);
	}

	public function testGetRowByOrderId() {
		global $wpdb;
		$row           = new Stdclass();
		$row->order_id = 42;
		$wpdb          = $this->getMockBuilder( \stdClass::class )
		                      ->addMethods( [ 'get_row', 'prepare' ] )
		                      ->getMock();
		$wpdb->prefix  = 'wp_';
		$wpdb->expects( $this->once() )
		     ->method( 'prepare' )
		     ->with( 'SELECT * FROM wp_alma_business_data WHERE order_id = %d', 42 )
		     ->willReturn('SELECT * FROM wp_alma_business_data WHERE order_id = 42');
		$wpdb->expects( $this->once() )
		     ->method( 'get_row' )
			 ->with('SELECT * FROM wp_alma_business_data WHERE order_id = 42')
		     ->willReturn( $row );
		$repository = new BusinessEventsRepository();
		$result     = $repository->getRowByOrderId( 42 );
		$this->assertEquals( $row, $result );
	}

	public function testGetRowByOrderIdNoRow() {
		global $wpdb;
		$wpdb = $this->getMockBuilder( \stdClass::class )
		                      ->addMethods( [ 'get_row', 'prepare' ] )
		                      ->getMock();
		$wpdb->prefix  = 'wp_';
		$wpdb->expects( $this->once() )
		     ->method( 'prepare' )
		     ->with( 'SELECT * FROM wp_alma_business_data WHERE order_id = %d', 99 )
		     ->willReturn('SELECT * FROM wp_alma_business_data WHERE order_id = 99');
		$wpdb->expects( $this->once() )
		     ->method( 'get_row' )
			 ->with('SELECT * FROM wp_alma_business_data WHERE order_id = 99')
		     ->willReturn( null );
		$repository = new BusinessEventsRepository();
		$result     = $repository->getRowByOrderId( 99 );
		$this->assertNull( $result );
	}

	public function testUpdateOrderId() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['update'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('update')
		     ->with(
			     'wp_alma_business_data',
			     ['order_id' => 101],
			     ['cart_id' => 202],
			     ['order_id' => '%d'],
			     ['cart_id' => '%d']
		     );

		$this->businessEventsRepository->updateOrderId(202, 101);
	}

	public function testSaveAlmaPaymentId() {
		global $wpdb;

		$wpdb = $this->getMockBuilder(\stdClass::class)
		             ->addMethods(['update'])
		             ->getMock();

		$wpdb->prefix = 'wp_';

		$wpdb->expects($this->once())
		     ->method('update')
		     ->with(
			     'wp_alma_business_data',
			     ['alma_payment_id' => 'payment_alma_12345'],
			     ['cart_id' => 303],
			     ['alma_payment_id' => '%s'],
			     ['cart_id' => '%d']
		     );

		$this->businessEventsRepository->saveAlmaPaymentId(303, 'payment_alma_12345');
	}
}