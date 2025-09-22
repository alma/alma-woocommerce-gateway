<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\TemplateHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;


class TemplateHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $templateHelper;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->templateHelper = new TemplateHelper();
		$pluginHelperAlias    = Mockery::mock( 'alias:Alma\Gateway\Application\Helper\PluginHelper' );
		$pluginHelperAlias->shouldReceive( 'getPluginPath' )->andReturn( 'var/plugin/path/' );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testLocateTemplateWithoutSubPath() {

		Functions\expect( 'apply_filters' )
			->once()
			->withArgs( function ( $key, $path, $name ) {
				$this->assertSame( 'alma_gateway_template', $key );
				$this->assertSame( 'var/plugin/path/public/templates/my-template.php', $path );
				$this->assertSame( 'my-template.php', $name );

				return true;
			} )
			->andReturn( 'returned_value' );

		$this->assertSame(
			'returned_value',
			$this->templateHelper->locateTemplate( 'my-template.php' )
		);

	}

	public function testLocateTemplateWithSubPath() {

		Functions\expect( 'apply_filters' )
			->once()
			->withArgs( function ( $key, $path, $name ) {
				$this->assertSame( 'alma_gateway_template', $key );
				$this->assertSame( 'var/plugin/path/public/templates/mysub/my-template.php', $path );
				$this->assertSame( 'my-template.php', $name );

				return true;
			} )
			->andReturn( 'returned_value' );

		$this->assertSame(
			'returned_value',
			$this->templateHelper->locateTemplate( 'my-template.php', 'mysub' )
		);

	}


}