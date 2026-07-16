<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Helper\CartHelper;
use Brain\Monkey;
use PHPUnit\Framework\TestCase;

class CartHelperTest extends TestCase {

	protected function setUp(): void {
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
	}

	// ─── generateUniqueCartId ────────────────────────────────────────────

	public function testGenerateUniqueCartIdReturnsAnInteger() {
		$id = CartHelper::generateUniqueCartId();
		$this->assertIsInt( $id );
	}

	public function testGenerateUniqueCartIdReturnsPositiveValue() {
		$id = CartHelper::generateUniqueCartId();
		$this->assertGreaterThan( 0, $id );
	}

	public function testGenerateUniqueCartIdFitsInBigintUnsigned() {
		$id  = CartHelper::generateUniqueCartId();
		$max = '18446744073709551615'; // BIGINT(20) UNSIGNED max

		$this->assertLessThanOrEqual( $max, (string) $id );
	}

	public function testGenerateUniqueCartIdFitsInPhpIntMax() {
		$id = CartHelper::generateUniqueCartId();
		$this->assertLessThanOrEqual( PHP_INT_MAX, $id );
	}

	public function testGenerateUniqueCartIdContainsTimestampInUpperBits() {
		$before = time();
		$id     = CartHelper::generateUniqueCartId();
		$after  = time();

		// Extract the timestamp by right-shifting 20 bits (RANDOM_BITS).
		$extracted = $id >> 20;

		$this->assertGreaterThanOrEqual( $before, $extracted );
		$this->assertLessThanOrEqual( $after, $extracted );
	}

	public function testGenerateUniqueCartIdRandomPartIsWithinBounds() {
		$id = CartHelper::generateUniqueCartId();

		// Lower 20 bits = random component.
		$randomPart = $id & ( ( 1 << 20 ) - 1 );

		$this->assertGreaterThanOrEqual( 0, $randomPart );
		$this->assertLessThanOrEqual( ( 1 << 20 ) - 1, $randomPart );
	}

	public function testGenerateUniqueCartIdProducesDifferentValues() {
		$sample_size = 100;
		$ids         = [];
		for ( $i = 0; $i < $sample_size; $i++ ) {
			$ids[] = CartHelper::generateUniqueCartId();
		}

		// All calls land in the same second, so uniqueness relies solely on the
		// 20-bit random component (2^20 values). By the birthday paradox a single
		// collision among 100 draws is expected in ~0.5% of runs, so asserting a
		// strict 100/100 uniqueness would be flaky. A near-perfect ratio still
		// proves the random component varies; a broken generator (e.g. constant
		// random or dropped bits) would collapse the ratio far below this bound.
		$unique_ratio = count( array_unique( $ids ) ) / $sample_size;
		$this->assertGreaterThanOrEqual( 0.95, $unique_ratio, 'Generated cart IDs should be almost entirely unique.' );
	}

	public function testGenerateUniqueCartIdIsRoughlyChronological() {
		$id1 = CartHelper::generateUniqueCartId();
		sleep( 1 ); // Ensure timestamp advances.
		$id2 = CartHelper::generateUniqueCartId();

		$this->assertGreaterThan( $id1, $id2, 'A later ID should be greater than an earlier one.' );
	}
}

