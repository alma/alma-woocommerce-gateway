<?php
/**
 * Class SettingsHelperBuilderTest
 *
 * @covers \Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder
 *
 * @package Alma_Gateway_For_Woocommerce
 */

namespace Alma\Woocommerce\Tests\Builders\Helpers;


use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder;
use Alma\Woocommerce\Factories\CurrencyFactory;
use Alma\Woocommerce\Factories\PluginFactory;
use Alma\Woocommerce\Factories\PriceFactory;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\AssetsHelper;
use Alma\Woocommerce\Helpers\InternationalizationHelper;
use Alma\Woocommerce\Helpers\SettingsHelper;
use Alma\Woocommerce\Helpers\ToolsHelper;
use WP_UnitTestCase;

/**
 * @covers \Alma\Woocommerce\Builders\Helpers\SettingsHelperBuilder
 */
class SettingsHelperBuilderTest extends WP_UnitTestCase {

	/**
	 * The settings helper builder.
	 *
	 * @var SettingsHelperBuilder $settings_helper_builder
	 */
	protected $settings_helper_builder;
	public function set_up() {
		$this->settings_helper_builder = new SettingsHelperBuilder();
	}
	
	public function test_get_instance() {
		$this->assertInstanceOf(SettingsHelper::class, $this->settings_helper_builder->get_instance());
	}

	public function test_get_internationalization_helper() {
		$this->assertInstanceOf(InternationalizationHelper::class, $this->settings_helper_builder->get_internalionalization_helper());
		$this->assertInstanceOf(InternationalizationHelper::class, $this->settings_helper_builder->get_internalionalization_helper(new InternationalizationHelper()));
	}

	public function test_get_version_factory() {
		$this->assertInstanceOf(VersionFactory::class, $this->settings_helper_builder->get_version_factory());
		$this->assertInstanceOf(VersionFactory::class, $this->settings_helper_builder->get_version_factory(new VersionFactory()));
	}

	public function test_get_tools_helper() {
		$this->assertInstanceOf(ToolsHelper::class, $this->settings_helper_builder->get_tools_helper());
		$this->assertInstanceOf(ToolsHelper::class, $this->settings_helper_builder->get_tools_helper(
			new ToolsHelper(
				new AlmaLogger(),
				new PriceFactory(),
				new CurrencyFactory()
			)
		));
	}

	public function test_get_assets_helper() {
		$this->assertInstanceOf(AssetsHelper::class, $this->settings_helper_builder->get_assets_helper());
		$this->assertInstanceOf(AssetsHelper::class, $this->settings_helper_builder->get_assets_helper(new AssetsHelper()));
	}

	public function test_get_plugin_factory() {
		$this->assertInstanceOf(PluginFactory::class, $this->settings_helper_builder->get_plugin_factory());
		$this->assertInstanceOf(PluginFactory::class, $this->settings_helper_builder->get_plugin_factory(new PluginFactory()));
	}
}



