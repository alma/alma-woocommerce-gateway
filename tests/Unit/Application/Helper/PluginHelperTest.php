<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Helper\PluginHelper;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Service\ContainerService;
use Brain\Monkey;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Run in separate process to allow mocking of functions like is_cart()
 *
 * @RunTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PluginHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;

	private $plugin;
	private $containerService;
	private $configService;
	private $contextHelper;

	public static function pageDataProvider(): array {
		return [
			"Is cart page"     => [ true, false, false ],
			"Is checkout page" => [ false, true, false ],
			"Is product page"  => [ false, false, true ],
		];
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
		$this->contextHelper->shouldReceive( 'isShop' )->never();

		$this->configService->shouldReceive( 'isConfigured' )->andReturn( false );
		$this->assertFalse( PluginHelper::isPluginNeeded() );
	}

	public function testPluginIsNeededWithIsConfiguredTrueAndBadPage(): void {
		$this->contextHelper->shouldReceive( 'isShop' )->once()->andReturn( false );

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
	 */
	public function testPluginIsNeededWithIsConfiguredTrueAndGoodPage( $cart, $checkout, $product ): void {
		$this->contextHelper->shouldReceive( 'isShop' )->once()->andReturn( true );

		$this->configService->shouldReceive( 'isConfigured' )->andReturn( true );
		$this->assertTrue( PluginHelper::isPluginNeeded() );
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

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->plugin           = Mockery::mock( 'alias:Alma\Gateway\Plugin' );
		$this->containerService = Mockery::mock( ContainerService::class );
		$this->configService    = Mockery::mock( ConfigService::class );
		$this->contextHelper    = Mockery::mock( 'alias:Alma\Gateway\Infrastructure\Helper\ContextHelper' );

		$this->containerService->shouldReceive( 'get' )
		                       ->with( ConfigService::class )
		                       ->andReturn( $this->configService );

		$this->plugin->shouldReceive( 'get_container' )
		             ->andReturn( $this->containerService );

	}

	protected function tearDown(): void {
		Mockery::close();
		Monkey\tearDown();
		parent::tearDown();
	}
}
