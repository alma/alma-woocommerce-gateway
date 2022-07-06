<?php

use PHPUnit\Framework\TestCase;

class AlmaWcShareOfCheckoutHelperTest extends TestCase {

	/**
	 * Call protected/private method of a class.
	 *
	 * @url https://jtreminio.com/blog/unit-testing-tutorial-part-iii-testing-protected-private-methods-coverage-reports-and-crap/
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 * @throws ReflectionException
	 */
	public function invokeMethod( &$object, $methodName, array $parameters = array() )
	{
		$reflection = new \ReflectionClass( get_class($object) );
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}

	public function data_provider_start_date() {
		return [
			"Normal Date" => [ '2022-07-01', '2022-07-01 00:00:00' ],
			"Past Date"   => [ '2019-01-01', '2019-01-01 00:00:00' ],
			"Futur Date"  => [ '2031-12-31', '2031-12-31 00:00:00' ],
		];
	}

	/**
	 * @dataProvider data_provider_start_date
	 */
	public function test_get_from_date( $start_date, $expected ) {
		$helper = new Alma_WC_Share_Of_Checkout_Helper( new Alma_WC_Helper_Order() );
		$result = $this->invokeMethod( $helper, 'get_from_date', array( $start_date ) );
		$this->assertEquals( $expected, $result );
	}

}
