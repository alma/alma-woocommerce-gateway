<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\API\Domain\Adapter\CartAdapterInterface;
use Alma\API\Domain\Adapter\ProductAdapterInterface;
use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use PHPUnit\Framework\TestCase;

class ExcludedProductsHelperTest extends TestCase {

	private $excludedProductsHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->excludedProductsHelper = new ExcludedProductsHelper();

	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->excludedProductsHelper = null;
	}

	/**
	 * @dataProvider canDisplayProvider
	 */
	public function testCanDisplayOnProductPage( $excludedCategories, $productCategories, $expected ) {
		$productInterface = $this->createMock( ProductAdapterInterface::class );
		$productInterface->method( 'getCategoryIds' )->willReturn( $productCategories );
		$this->assertSame( $expected,
			$this->excludedProductsHelper->canDisplayOnProductPage( $productInterface, $excludedCategories ) );
	}

	/**
	 * @dataProvider canDisplayProvider
	 */
	public function testCanDisplayCartPage( $excludedCategories, $productCategories, $expected ) {
		$cartAdapterInterface = $this->createMock( CartAdapterInterface::class );
		$cartAdapterInterface->method( 'getCartItemsCategories' )->willReturn( $productCategories );
		$this->assertSame( $expected,
			$this->excludedProductsHelper->canDisplayOnCartPage( $cartAdapterInterface, $excludedCategories )
		);
	}

	/**
	 * @dataProvider canDisplayProvider
	 */
	public function testCanDisplayOnCheckoutPage( $excludedCategories, $productCategories, $expected ) {
		$cartAdapterInterface = $this->createMock( CartAdapterInterface::class );
		$cartAdapterInterface->method( 'getCartItemsCategories' )->willReturn( $productCategories );
		$this->assertSame( $expected,
			$this->excludedProductsHelper->canDisplayOnCheckoutPage( $cartAdapterInterface, $excludedCategories )
		);
	}


	public function canDisplayProvider() {
		return [
			'empty excluded categories' => [ [], [ 1, 2, 3 ], true ],
			'no match'                  => [ [ 4, 5 ], [ 1, 2, 3 ], true ],
			'match one category'        => [ [ 3, 4 ], [ 1, 2, 3 ], false ],
			'match all categories'      => [ [ 1, 2, 3 ], [ 1, 2, 3 ], false ],
			'match some categories'     => [ [ 2, 3, 4 ], [ 1, 2, 3 ], false ],
		];
	}

}