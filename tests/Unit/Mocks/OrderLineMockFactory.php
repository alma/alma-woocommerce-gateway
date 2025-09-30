<?php

namespace Alma\Gateway\Tests\Unit\Mocks;

use Alma\Gateway\Infrastructure\Adapter\OrderLineAdapter;
use Alma\Gateway\Infrastructure\Adapter\ProductAdapter;
use PHPUnit\Framework\TestCase;

class OrderLineMockFactory {
	public static function create( TestCase $testCase ): OrderLineAdapter {
		$productAdapterMock = $testCase
			->getMockBuilder( ProductAdapter::class )
			->disableOriginalConstructor()
			->getMock();
		$productAdapterMock->method( 'getSku' )->willReturn( 'TESTSKU' );
		$productAdapterMock->method( 'getId' )->willReturn( 123 );
		$productAdapterMock->method( 'getPrice' )->willReturn( 1010 );
		$productAdapterMock->method( 'getImageId' )->willReturn( 456 );
		$productAdapterMock->method( 'getPermalink' )->willReturn( 'http://example.com/product/test-product' );
		$productAdapterMock->method( 'needsShipping' )->willReturn( true );

		$orderLineMock = $testCase
			->getMockBuilder( OrderLineAdapter::class )
			->disableOriginalConstructor()
			->getMock();
		$orderLineMock->method( 'getName' )->willReturn( 'TESTNAME' );
		$orderLineMock->method( 'getProduct' )->willReturn( $productAdapterMock );
		$orderLineMock->method( 'getQuantity' )->willReturn( 2 );
		$orderLineMock->method( 'getTotal' )->willReturn( 2020 );

		return $orderLineMock;
	}

}