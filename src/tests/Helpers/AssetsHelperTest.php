<?php
/**
 * Class AssetsHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\AssetsHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\Helpers\AssetsHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\AssetsHelper
 */
class AssetsHelperTest extends WP_UnitTestCase {

	/**
	 * @var AssetsHelper
	 */
	protected $assets_helper;

	public function set_up() {
		$this->assets_helper = new AssetsHelper();
	}
	public function test_alma_domains_whitelist() {
		$this->assertEquals(array(
			'testdomain',
			'pay.getalma.eu',
			'pay.sandbox.getalma.eu'
		), $this->assets_helper->alma_domains_whitelist(array('testdomain')));
	}
}



