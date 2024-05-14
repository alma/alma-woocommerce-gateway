<?php
/**
 * Class InternationalizationHelperTest
 *
 * @covers \Alma\Woocommerce\Helpers\InternationalizationHelper
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Helpers;

use Alma\Woocommerce\Helpers\InternationalizationHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Helpers\InternationalizationHelper
 */
class InternationalizationHelperTest extends WP_UnitTestCase {

	/**
	 * @var InternationalizationHelper
	 */
	protected $internationalization_helper;

	public function set_up() {
		$this->internationalization_helper = new InternationalizationHelper();
	}

	public function test_get_display_texts_keys_and_values() {
		$this->assertEquals(array( 'at_shipping' => 'At shipping'), $this->internationalization_helper->get_display_texts_keys_and_values());
	}
}



