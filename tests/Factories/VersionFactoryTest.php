<?php
/**
 * Class VersionFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\VersionFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\VersionFactory
 */
class VersionFactoryTest extends WP_UnitTestCase {

	/**
	 * VersionFactory.
	 *
	 * @var VersionFactory $version_factory
	 */
	protected $version_factory;
	public function set_up() {
		$this->version_factory = new VersionFactory();
	}

	public function test_get_session_factory() {
		$this->assertEquals(\WooCommerce::instance()->version, $this->version_factory->get_version());
	}

}



