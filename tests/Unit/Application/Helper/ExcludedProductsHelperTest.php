<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\ExcludedProductsHelper;
use Alma\Gateway\Infrastructure\Adapter\CartAdapter;
use Alma\Gateway\Infrastructure\Adapter\ProductAdapter;
use PHPUnit\Framework\TestCase;

class ExcludedProductsHelperTest extends TestCase {

	private $excludedProductsHelper;

	public static function canDisplayProvider() {
		return [
			'empty excluded categories' => [ [], [ 'A', 'B', 'C' ], true ],
			'no match'                  => [ [ 'D', 'E' ], [ 'A', 'B', 'C' ], true ],
			'match one category'        => [ [ 'C', 'D' ], [ 'A', 'B', 'C' ], false ],
			'match all categories'      => [ [ 'A', 'B', 'C' ], [ 'A', 'B', 'C' ], false ],
			'match some categories'     => [ [ 'B', 'C', 'D' ], [ 'A', 'B', 'C' ], false ],
		];
	}

	/**
	 * @dataProvider canDisplayProvider
	 */
	public function testCanDisplayOnProductPage( $excludedCategories, $productCategories, $expected ) {
		$productInterface = $this->createMock( ProductAdapter::class );
		$productInterface->method( 'getCategorySlugs' )->willReturn( $productCategories );
		$this->assertSame( $expected,
			$this->excludedProductsHelper->canDisplayOnProductPage( $productInterface, $excludedCategories ) );
	}

	/**
	 * @dataProvider canDisplayProvider
	 */
	public function testCanDisplayCartPage( $excludedCategories, $productCategories, $expected ) {
		$cartAdapterInterface = $this->createMock( CartAdapter::class );
		$cartAdapterInterface->method( 'getCartItemsCategoriesSlugs' )->willReturn( $productCategories );
		$this->assertSame( $expected,
			$this->excludedProductsHelper->canDisplayOnCartPage( $cartAdapterInterface, $excludedCategories )
		);
	}

	/**
	 * @dataProvider canDisplayProvider
	 */
	public function testCanDisplayOnCheckoutPage( $excludedCategories, $productCategories, $expected ) {
		$cartAdapterInterface = $this->createMock( CartAdapter::class );
		$cartAdapterInterface->method( 'getCartItemsCategoriesSlugs' )->willReturn( $productCategories );
		$this->assertSame( $expected,
			$this->excludedProductsHelper->canDisplayOnCheckoutPage( $cartAdapterInterface, $excludedCategories )
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->excludedProductsHelper = new ExcludedProductsHelper();

	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->excludedProductsHelper = null;
	}

}
