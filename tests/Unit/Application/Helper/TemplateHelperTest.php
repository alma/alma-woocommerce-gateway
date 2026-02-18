<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\TemplateHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\when;

/**
 * Exécute cette classe de tests dans un processus séparé pour éviter
 * le conflit "class already exists" quand on crée un alias Mockery.
 *
 * @preserveGlobalState disabled
 */
class TemplateHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private ?TemplateHelper $templateHelper;
	private $pluginHelper;

	/**
	 * @return void
	 *
	 */
	public function testLocateTemplateWithoutSubPath() {

		Functions\expect( 'apply_filters' )
			->once()
			->withArgs( function ( $key, $path, $name ) {
				$this->assertSame( 'alma_gateway_template', $key );
				$this->assertSame( '/app/woocommerce/wp-content/plugins/alma-gateway-for-woocommerce/public/templates/my-template.php',
					$path );
				$this->assertSame( 'my-template.php', $name );

				return true;
			} )
			->andReturn( 'returned_value' );

		$this->assertSame(
			'returned_value',
			$this->templateHelper->locateTemplate( 'my-template.php' )
		);

	}

	/**
	 * @return void
	 */
	public function testLocateTemplateWithSubPath() {

		Functions\expect( 'apply_filters' )
			->once()
			->withArgs( function ( $key, $path, $name ) {
				$this->assertSame( 'alma_gateway_template', $key );
				$this->assertSame( '/app/woocommerce/wp-content/plugins/alma-gateway-for-woocommerce/public/templates/mysub/my-template.php',
					$path );
				$this->assertSame( 'my-template.php', $name );

				return true;
			} )
			->andReturn( 'returned_value' );

		$this->assertSame(
			'returned_value',
			$this->templateHelper->locateTemplate( 'my-template.php', 'mysub' )
		);

	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		when( 'plugins_url' )->justReturn( 'http://woocommerce-10-3-5.local.test/wp-content/plugins/alma-gateway-for-woocommerce/' );
		when( 'plugin_dir_path' )->justReturn( '/app/woocommerce/wp-content/plugins/alma-gateway-for-woocommerce/' );

		$this->pluginHelper = Mockery::mock( 'alias:Alma\Gateway\Application\Helper\PluginHelper' );
		$this->pluginHelper->shouldReceive( 'getPluginPath' )->andReturn( 'var/plugin/path/' );
		$this->templateHelper = new TemplateHelper();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::resetContainer();
		Mockery::close();
		parent::tearDown();
		$this->templateHelper = null;
		$this->pluginHelper   = null;
	}


}
