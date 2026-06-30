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
		$ids = [];
		for ( $i = 0; $i < 100; $i++ ) {
			$ids[] = CartHelper::generateUniqueCartId();
		}

		// With 2^20 random possibilities per second, 100 consecutive calls
		// should virtually never collide.
		$unique = array_unique( $ids );
		$this->assertCount( count( $ids ), $unique, 'Expected all generated IDs to be unique.' );
	}

	public function testGenerateUniqueCartIdIsRoughlyChronological() {
		$id1 = CartHelper::generateUniqueCartId();
		sleep( 1 ); // Ensure timestamp advances.
		$id2 = CartHelper::generateUniqueCartId();

		$this->assertGreaterThan( $id1, $id2, 'A later ID should be greater than an earlier one.' );
	}
}

