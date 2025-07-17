<?php

namespace Alma\Gateway\Tests\Unit\WooCommerce\Proxy;

use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use PHPUnit\Framework\TestCase;

class WooCommerceProxyTest extends TestCase {

	public function test_convert_price_to_cents() {
		// Assuming WooCommerceProxy has a method to convert price to cents
		$price = 10.99;
		$cents = WooCommerceProxy::price_to_cent( $price );
		$this->assertEquals( 1099, $cents );
	}

	public function test_convert_price_to_euros() {
		// Assuming WooCommerceProxy has a method to convert cents to euros
		$cents = 1099;
		$euros = WooCommerceProxy::price_to_euro( $cents );
		$this->assertEquals( 10.99, $euros );
	}
}