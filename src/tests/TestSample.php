<?php
/**
 * Class TestSample
 *
 * @package Alma_Gateway_For_Woocommerce
 */

 use Alma\Woocommerce\Alma_Refund;

/**
 * Sample test case.
 */
class TestSample extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$foo = new Alma_Refund();

		$this->assertTrue( defined('WC_VERSION') );
	}
}
