<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Service\ContainerServiceException;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PluginHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $plugin;
	private $containerService;
	private $configService;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->plugin           = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$this->containerService = Mockery::mock( ContainerService::class );
		$this->configService    = Mockery::mock( ConfigService::class );

		$this->containerService->shouldReceive( 'get' )
		                       ->with( ConfigService::class )
		                       ->andReturn( $this->configService );

		$this->plugin->shouldReceive( 'get_container' )
		             ->andReturn( $this->containerService );

	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testIsConfiguredTrue(): void {
		$this->configService->shouldReceive( 'isConfigured' )->andReturn( true );
		$this->assertTrue( PluginHelper::isConfigured() );
	}

	public function testIsConfiguredFalse(): void {
		$this->configService->shouldReceive( 'isConfigured' )->andReturn( false );
		$this->assertFalse( PluginHelper::isConfigured() );
	}

	public function testPluginIsNeededWithIsConfiguredFalse(): void {
		Functions\expect( 'is_cart' )->never();
		Functions\expect( 'is_checkout' )->never();
		Functions\expect( 'is_product' )->never();
		$this->configService->shouldReceive( 'isConfigured' )->andReturn( false );
		$this->assertFalse( PluginHelper::isPluginNeeded() );
	}

	public function testPluginIsNeededWithIsConfiguredTrueAndBadPage(): void {
		Functions\expect( 'is_cart' )->once()->andReturn( false );
		Functions\expect( 'is_checkout' )->once()->andReturn( false );
		Functions\expect( 'is_product' )->once()->andReturn( false );

		$this->configService->shouldReceive( 'isConfigured' )->andReturn( true );
		$this->assertFalse( PluginHelper::isPluginNeeded() );
	}

	/**
	 * @dataProvider pageDataProvider
	 *
	 * @param $cart
	 * @param $checkout
	 * @param $product
	 *
	 * @return void
	 * @throws ContainerServiceException
	 */
	public function testPluginIsNeededWithIsConfiguredTrueAndGoodPage( $cart, $checkout, $product ): void {
		Functions\expect( 'is_cart' )->andReturn( $cart );
		Functions\expect( 'is_checkout' )->andReturn( $checkout );
		Functions\expect( 'is_product' )->andReturn( $product );

		$this->configService->shouldReceive( 'isConfigured' )->andReturn( true );
		$this->assertTrue( PluginHelper::isPluginNeeded() );
	}

	public static function pageDataProvider(): array {
		return [
			"Is cart page"     => [ true, false, false ],
			"Is checkout page" => [ false, true, false ],
			"Is product page"  => [ false, false, true ],
		];
	}

	public function testGetterAndSetterPluginUrl(): void {
		$testUrl = 'https://example.com/plugin-url';
		PluginHelper::setPluginUrl( $testUrl );
		$this->assertEquals( $testUrl, PluginHelper::getPluginUrl() );
	}

	public function testGetterAndSetterPluginPath() {
		$testPath = '/path/to/plugin';
		PluginHelper::setPluginPath( $testPath );
		$this->assertEquals( $testPath, PluginHelper::getPluginPath() );
	}

	public function testGetterAndSetterPluginFile() {
		$testFile = '/path/to/plugin/plugin-file.php';
		PluginHelper::setPluginFile( $testFile );
		$this->assertEquals( $testFile, PluginHelper::getPluginFile() );
	}


}

