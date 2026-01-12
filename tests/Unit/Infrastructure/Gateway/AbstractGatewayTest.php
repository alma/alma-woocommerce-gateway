<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Gateway;

use Alma\Gateway\Infrastructure\Adapter\FeePlanListAdapter;
use Alma\Gateway\Infrastructure\Gateway\AbstractGateway;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractGatewayTest extends TestCase {
	/**
	 * @var FeePlanRepository|(FeePlanRepository&object&MockObject)|(FeePlanRepository&MockObject)|(object&MockObject)|MockObject
	 */
	private $feePlanRepositoryMock;

	public function setUp(): void {
		Monkey\setUp();

		Functions\when( '__' )->returnArg();

		// Mock les hooks WordPress pour éviter l'erreur
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( true );
		Functions\when( 'almalog' )->justReturn( true );

		$this->feePlanRepositoryMock = $this->createMock( FeePlanRepository::class );
		$this->gateway               = new class( $this->feePlanRepositoryMock ) extends AbstractGateway {
			protected const PAYMENT_METHOD = 'test';
		};
	}

	public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
		$this->gateway = null;
	}

	public function testIsEnabledWithFeePlanReturnTrue(): void {
		$filteredListAdapter = $this->getMockBuilder( FeePlanListAdapter::class )
		                            ->disableOriginalConstructor()
		                            ->getMock();
		$filteredListAdapter->method( 'count' )->willReturn( 2 );

		$feePlanListAdapter = $this->getMockBuilder( FeePlanListAdapter::class )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$feePlanListAdapter->method( 'filterFeePlanList' )
		                   ->with( [ 'test' ] )
		                   ->willReturnSelf();
		$feePlanListAdapter->method( 'filterEnabled' )
		                   ->willReturn( $filteredListAdapter );

		$this->feePlanRepositoryMock->method( 'getAll' )->willReturn( $feePlanListAdapter );

		$this->assertTrue( $this->gateway->is_enabled() );
	}

	public function testIsEnabledWithoutFeePlanReturnFalse(): void {
		$filteredListAdapter = $this->getMockBuilder( FeePlanListAdapter::class )
		                            ->disableOriginalConstructor()
		                            ->getMock();
		$filteredListAdapter->method( 'count' )->willReturn( 0 );

		$feePlanListAdapter = $this->getMockBuilder( FeePlanListAdapter::class )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$feePlanListAdapter->method( 'filterFeePlanList' )
		                   ->with( [ 'test' ] )
		                   ->willReturnSelf();
		$feePlanListAdapter->method( 'filterEnabled' )
		                   ->willReturn( $filteredListAdapter );

		$this->feePlanRepositoryMock->method( 'getAll' )->willReturn( $feePlanListAdapter );

		$this->assertFalse( $this->gateway->is_enabled() );
	}
}
