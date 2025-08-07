<?php

namespace Alma\Gateway\Tests\Unit\Business\Helper;

use Alma\Gateway\Application\Helper\DisplayHelper;
use PHPUnit\Framework\TestCase;

class DisplayHelperTest extends TestCase {
	public function test_convert_price_to_cents() {
		$price = 10.99;
		$cents = DisplayHelper::price_to_cent( $price );
		$this->assertEquals( 1099, $cents );
	}

	public function test_convert_integer_price_to_cents() {
		$price = 10;
		$cents = DisplayHelper::price_to_cent( $price );
		$this->assertEquals( 1000, $cents );
	}

	public function test_convert_price_to_euros() {
		$cents = 1099;
		$euros = DisplayHelper::price_to_euro( $cents );
		$this->assertEquals( 10.99, $euros );
	}

	public function test_convert_price_to_floating_euros() {
		$cents = 1000;
		$euros = DisplayHelper::price_to_euro( $cents );
		$this->assertEquals( 10, $euros );
	}
}