<?php
/**
 * Class SessionFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\CurrencyFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Factories\SessionFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\SessionFactory
 */
class SessionFactoryTest extends WP_UnitTestCase {

	/**
	 * SessionFactory.
	 *
	 * @var SessionFactory $session_factory
	 */
	protected $session_factory;
	public function set_up() {
		$this->session_factory = new SessionFactory();
	}

	public function test_get_session_factory() {
		$this->assertInstanceOf(\WC_Session::class, $this->session_factory->get_session());
	}

}



