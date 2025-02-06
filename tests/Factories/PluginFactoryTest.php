<?php

/**
 * Class PluginFactoryTest
 *
 * @covers \Alma\Woocommerce\Factories\PluginFactory
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Factories;

use Alma\Woocommerce\Admin\Services\NoticesService;
use Alma\Woocommerce\Factories\PluginFactory;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Factories\PluginFactory
 */
class PluginFactoryTest extends WP_UnitTestCase {

	/**
	 * PluginFactory.
	 *
	 * @var PluginFactory $plugin_factory
	 */
	protected $plugin_factory;
	public function set_up() {
		$this->plugin_factory = new PluginFactory();
	}

	public function test_get_plugin_admin_notice() {
		$this->assertInstanceOf(NoticesService::class, $this->plugin_factory->get_plugin_admin_notice());
	}

}



