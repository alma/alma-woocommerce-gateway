<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Helper\CollectCmsDataHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class CollectCmsDataHelperTest extends TestCase {

	private CollectCmsDataHelper $helper;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( 'plugins_url' )->justReturn( 'http://example.com/wp-content/plugins/alma-gateway-for-woocommerce/' );
		Functions\when( 'plugin_dir_path' )->justReturn( '/app/wp-content/plugins/alma-gateway-for-woocommerce/' );

		$this->helper = new CollectCmsDataHelper();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	// ─── getPaymentMethodPosition ────────────────────────────────────────

	public function testGetPaymentMethodPositionReturnsZeroWhenOptionIsEmpty(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$this->assertSame( 0, $this->helper->getPaymentMethodPosition() );
	}

	public function testGetPaymentMethodPositionReturnsZeroWhenAlmaConfigNotInOption(): void {
		Functions\when( 'get_option' )->justReturn( array( 'bacs' => 0, 'cheque' => 1 ) );

		$this->assertSame( 0, $this->helper->getPaymentMethodPosition() );
	}

	public function testGetPaymentMethodPositionReturnsCorrectPosition(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'bacs'                 => 0,
				'cheque'               => 1,
				'alma_config_gateway'  => 2,
				'cod'                  => 3,
			)
		);

		// paypal(1), cheque(2), alma_config_gateway(3) — position 3.
		$this->assertSame( 3, $this->helper->getPaymentMethodPosition() );
	}

	public function testGetPaymentMethodPositionExcludesAlmaFrontendGateways(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'bacs'                  => 0,
				'alma_config_gateway'   => 1,
				'alma_pay_now_gateway'  => 1,
				'alma_pnx_gateway'      => 1,
				'alma_pay_later_gateway' => 1,
				'cod'                   => 2,
			)
		);

		// Alma frontend gateways filtered out → bacs(1), alma_config_gateway(2).
		$this->assertSame( 2, $this->helper->getPaymentMethodPosition() );
	}

	// ─── getSpecificFeatures ────────────────────────────────────────────

	public function testGetSpecificFeaturesReturnsAutoUpdateWhenPluginHasAutoUpdate(): void {
		$pluginBasename = 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php';
		Functions\when( 'get_option' )
			->justReturn( array( $pluginBasename, 'woocommerce/woocommerce.php' ) );
		Functions\when( 'plugin_basename' )->justReturn( $pluginBasename );

		$result = $this->helper->getSpecificFeatures();

		$this->assertContains( 'auto_update', $result );
	}

	public function testGetSpecificFeaturesReturnsEmptyArrayWhenNoAutoUpdate(): void {
		Functions\when( 'get_option' )->justReturn( array( 'woocommerce/woocommerce.php' ) );
		Functions\when( 'plugin_basename' )->justReturn( 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php' );

		$result = $this->helper->getSpecificFeatures();

		$this->assertNotContains( 'auto_update', $result );
		$this->assertEmpty( $result );
	}

	public function testGetSpecificFeaturesReturnsEmptyArrayWhenAutoUpdateOptionIsEmpty(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'plugin_basename' )->justReturn( 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php' );

		$this->assertEmpty( $this->helper->getSpecificFeatures() );
	}

	// ─── isMultisite ────────────────────────────────────────────────────

	public function testIsMultisiteReturnsTrueWhenMultisiteEnabled(): void {
		Functions\when( 'is_multisite' )->justReturn( true );

		$this->assertTrue( $this->helper->isMultisite() );
	}

	public function testIsMultisiteReturnsFalseWhenMultisiteDisabled(): void {
		Functions\when( 'is_multisite' )->justReturn( false );

		$this->assertFalse( $this->helper->isMultisite() );
	}

	// ─── getCmsVersion ──────────────────────────────────────────────────

	public function testGetCmsVersionReturnsWcVersion(): void {
		$wc          = new \stdClass();
		$wc->version = '9.0.0';
		Functions\when( 'WC' )->justReturn( $wc );

		$this->assertSame( '9.0.0', $this->helper->getCmsVersion() );
	}

	public function testGetCmsVersionReturnsEmptyStringWhenWcReturnsFalse(): void {
		Functions\when( 'WC' )->justReturn( false );

		$this->assertSame( '', $this->helper->getCmsVersion() );
	}

	// ─── getThirdPartiesPlugins ──────────────────────────────────────────

	public function testGetThirdPartiesPluginsReturnsFormattedPluginsExcludingAlma(): void {
		$almaBasename = 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php';

		Functions\when( 'get_plugins' )->justReturn(
			array(
				'woocommerce/woocommerce.php'                                    => array( 'Name' => 'WooCommerce', 'Version' => '9.0.0' ),
				$almaBasename                                                    => array( 'Name' => 'Alma', 'Version' => '6.3.0' ),
				'query-monitor/query-monitor.php'                                => array( 'Name' => 'Query Monitor', 'Version' => '3.16.0' ),
			)
		);
		Functions\when( 'get_option' )->justReturn(
			array( 'woocommerce/woocommerce.php', $almaBasename, 'query-monitor/query-monitor.php' )
		);
		Functions\when( 'plugin_basename' )->justReturn( $almaBasename );

		$result = $this->helper->getThirdPartiesPlugins();

		$this->assertCount( 2, $result );
		$this->assertSame( array( 'name' => 'WooCommerce', 'version' => '9.0.0' ), $result[0] );
		$this->assertSame( array( 'name' => 'Query Monitor', 'version' => '3.16.0' ), $result[1] );
	}

	public function testGetThirdPartiesPluginsReturnsEmptyArrayWhenNoActivePlugins(): void {
		Functions\when( 'get_plugins' )->justReturn( array() );
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'plugin_basename' )->justReturn( 'alma-gateway-for-woocommerce/alma-gateway-for-woocommerce.php' );

		$this->assertSame( array(), $this->helper->getThirdPartiesPlugins() );
	}

	// ─── getThemeName / getThemeVersion ─────────────────────────────────

	public function testGetThemeNameReturnsActiveThemeName(): void {
		$themeMock = new class {
			public function get( string $key ): string {
				return 'name' === strtolower( $key ) ? 'Storefront' : '';
			}
		};
		Functions\when( 'wp_get_theme' )->justReturn( $themeMock );

		$this->assertSame( 'Storefront', $this->helper->getThemeName() );
	}

	public function testGetThemeVersionReturnsActiveThemeVersion(): void {
		$themeMock = new class {
			public function get( string $key ): string {
				return 'version' === strtolower( $key ) ? '4.2.0' : '';
			}
		};
		Functions\when( 'wp_get_theme' )->justReturn( $themeMock );

		$this->assertSame( '4.2.0', $this->helper->getThemeVersion() );
	}

	// ─── getCollectCmsDataUrl ───────────────────────────────────────────

	public function testGetCollectCmsDataUrlBuildsWcApiUrlFromHomeUrl(): void {
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		Functions\when( 'add_query_arg' )->alias(
			function ( $key, $value, $url ) {
				return $url . '?' . $key . '=' . $value;
			}
		);

		$this->assertSame(
			'https://example.com/?wc-api=alma_collect_cms_data',
			$this->helper->getCollectCmsDataUrl()
		);
	}
}
