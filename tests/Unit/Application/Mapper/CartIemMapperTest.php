<?php

namespace Alma\Gateway\Tests\Unit\Application\Mapper;

use Alma\API\Application\DTO\CartItemDto;
use Alma\Gateway\Application\Mapper\CartItemMapper;
use Alma\Gateway\Infrastructure\Repository\ProductCategoryRepository;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Alma\Gateway\Tests\Unit\Mocks\OrderLineMockFactory;
use Mockery;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class CartIemMapperTest extends TestCase {

	private $cartIemMapper;

	protected function setUp(): void {
		Monkey\setUp();
		$this->cartIemMapper = new CartItemMapper();
	}

	protected function tearDown(): void {
		Mockery::resetContainer();
		Mockery::close();
		Monkey\tearDown();
		parent::tearDown();

		$this->cartIemMapper = null;
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testBuildCartItemDetails(): void {
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

		$orderLineMock = OrderLineMockFactory::create( $this );

		$cartItemDetail = $this->cartIemMapper->buildCartItemDetails( $orderLineMock );
		$this->assertInstanceOf( CartItemDto::class, $cartItemDetail );
		$this->assertSame(
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
			],
			$cartItemDetail->toArray()
		);

	}

}