<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\DisplayHelper;
use PHPUnit\Framework\TestCase;

class DisplayHelperTest extends TestCase {

	public function testFormatAmount() {
		$this->assertSame( '10 €', DisplayHelper::amount( 10 ) );
		$this->assertSame( '10.10 €', DisplayHelper::amount( 10.1 ) );
		$this->assertSame( '10.12 €', DisplayHelper::amount( 10.12 ) );
	}

	public function testFormatPriceToCent() {
		$this->assertSame( 1100, DisplayHelper::price_to_cent( 11 ) );
		$this->assertSame( 1020, DisplayHelper::price_to_cent( 10.2 ) );
		$this->assertSame( 1013, DisplayHelper::price_to_cent( 10.13 ) );
	}

	public function testFormatPriceToDecimal() {
		$this->assertSame( 13.00, DisplayHelper::price_to_euro( 1300 ) );
		$this->assertSame( 10.30, DisplayHelper::price_to_euro( 1030 ) );
		$this->assertSame( 10.16, DisplayHelper::price_to_euro( 1016 ) );
	}

}