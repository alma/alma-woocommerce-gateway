<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\API\Application\DTO\CartDto;
use Alma\API\Domain\Adapter\OrderAdapterInterface;
use Alma\Gateway\Application\Mapper\CartMapper;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Alma\Gateway\Tests\Unit\Mocks\OrderLineMockFactory;
use Mockery;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class CartMapperTest extends TestCase {
	protected function setUp(): void {
		Monkey\setUp();
		$this->cartMapper = new CartMapper();
	}

	protected function tearDown(): void {
		Mockery::resetContainer();
		Mockery::close();
		Monkey\tearDown();

		$this->cartMapper = null;
	}

	public function testBuildCartDetailsWithoutItems(): void {
		$orderAdapterMock = $this->createMock( OrderAdapterInterface::class );
		$orderAdapterMock->method( 'getOrderLines' )->willReturn( [] );
		$cartDto = $this->cartMapper->buildCartDetails( $orderAdapterMock );
		$this->assertInstanceOf( CartDto::class, $cartDto );
		$this->assertSame( [ 'items' => [] ], $cartDto->toArray() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testBuildCartDetailsWithItems(): void {
		Functions\expect( 'wp_get_attachment_url' )
			->once()
			->with( 456 )
			->andReturn( 'http://example.com/wp-content/uploads/2023/01/image.jpg' );

		$pluginMock                = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$containerMock             = Mockery::mock( ContainerService::class );
		$productCategoryRepository = Mockery::mock( ProductCategoryRepository::class );
		$productCategoryRepository->shouldReceive( 'findByProductId' )
		                          ->with( 123 )
		                          ->andReturn( [ 'Category1', 'Category2' ] );

		$containerMock->shouldReceive( 'get' )
		              ->with( ProductCategoryRepository::class )
		              ->andReturn( $productCategoryRepository );

		$pluginMock->shouldReceive( 'get_container' )
		           ->once()
		           ->andReturn( $containerMock );

		$orderAdapterMock = $this->createMock( OrderAdapterInterface::class );
		$orderAdapterMock->method( 'getOrderLines' )->willReturn( [ OrderLineMockFactory::create( $this ) ] );
		$cartDto = $this->cartMapper->buildCartDetails( $orderAdapterMock );
		$this->assertInstanceOf( CartDto::class, $cartDto );
		$this->assertEquals(
			[
				'items' => [
					[
						'sku'               => 'TESTSKU',
						'title'             => 'TESTNAME',
						'quantity'          => 2,
						'unit_price'        => 1010,
						'line_price'        => 2020,
						'categories'        => [ 'Category1', 'Category2' ],
						'url'               => 'http://example.com/product/test-product',
						'picture_url'       => 'http://example.com/wp-content/uploads/2023/01/image.jpg',
						'requires_shipping' => true,
					]
				]
			],
			$cartDto->toArray() );
	}
}