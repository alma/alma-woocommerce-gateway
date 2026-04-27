<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Helper;

use Alma\Gateway\Infrastructure\Helper\ContextHelper;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

class ContextHelperTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset superglobals before each test.
		$_GET    = [];
		$_SERVER = [];
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function testIsAdminReturnsTrueForClassicAdminPage() {
		Functions\expect( 'is_admin' )->once()->andReturn( true );

		$this->assertTrue( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsTrueForRestRouteWithUglyPermalinks() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$_GET['rest_route'] = '/wc-admin/settings/payments';

		$this->assertTrue( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsTrueForRequestUriWithPrettyPermalinks() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$_SERVER['REQUEST_URI'] = '/wp-json/wc-admin/settings/payments/alma';

		$this->assertTrue( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsFalseForFrontendPage() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$_SERVER['REQUEST_URI'] = '/checkout/';

		$this->assertFalse( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsFalseWhenRequestUriIsEmpty() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$this->assertFalse( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsTrueForWcAdminCaseInsensitive() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$_SERVER['REQUEST_URI'] = '/wp-json/WC-Admin/settings';

		$this->assertTrue( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsFalseForStoreApiRequest() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';

		$this->assertFalse( ContextHelper::isAdmin() );
	}

	public function testIsAdminReturnsFalseWhenWcAdminInQueryString() {
		Functions\expect( 'is_admin' )->once()->andReturn( false );

		$_SERVER['REQUEST_URI'] = '/checkout/?ref=wc-admin-promo';

		$this->assertFalse( ContextHelper::isAdmin() );
	}

	public function testIsAdminPrioritisesIsAdminOverSuperglobals() {
		// is_admin() returns true, so superglobals should not matter.
		Functions\expect( 'is_admin' )->once()->andReturn( true );

		$_GET['rest_route']     = '/wc/store/v1/checkout';
		$_SERVER['REQUEST_URI'] = '/shop/product/';

		$this->assertTrue( ContextHelper::isAdmin() );
	}

	public function testIsGatewaySettingsPageReturnsTrueForRestRouteUglyPermalinks() {
		$_GET['rest_route'] = '/wc-admin/settings/payments';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageReturnsTrueForPrettyPermalinks() {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc-admin/settings/payments/alma';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageReturnsTrueForClassicWcSettingsPage() {
		$_GET['page'] = 'wc-settings';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageReturnsFalseForOtherAdminPage() {
		$_GET['page'] = 'wc-orders';

		$this->assertFalse( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageReturnsFalseForFrontendPage() {
		$_SERVER['REQUEST_URI'] = '/checkout/';

		$this->assertFalse( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageReturnsFalseWhenNoGetOrServerVars() {
		$this->assertFalse( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageWithAlmaGatewayFlagReturnsTrueWhenSectionMatches() {
		$_GET['page']    = 'wc-settings';
		$_GET['section'] = 'alma_config_gateway';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage( true ) );
	}

	public function testIsGatewaySettingsPageWithAlmaGatewayFlagReturnsFalseWhenSectionDoesNotMatch() {
		$_GET['page']    = 'wc-settings';
		$_GET['section'] = 'paypal';

		$this->assertFalse( ContextHelper::isGatewaySettingsPage( true ) );
	}

	public function testIsGatewaySettingsPageWithAlmaGatewayFlagReturnsFalseWhenNoSection() {
		$_GET['page'] = 'wc-settings';

		$this->assertFalse( ContextHelper::isGatewaySettingsPage( true ) );
	}

	public function testIsGatewaySettingsPagePrettyPermalinksTakesPriorityOverAlmaFlag() {
		// Pretty permalink REST route should return true even when $isAlmaGatewaySettingPage is true,
		// because the REST check comes first and the flag only applies to classic admin pages.
		$_SERVER['REQUEST_URI'] = '/wp-json/wc-admin/settings/payments/alma';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage( true ) );
	}

	public function testIsGatewaySettingsPageIsCaseInsensitiveOnRequestUri() {
		$_SERVER['REQUEST_URI'] = '/wp-json/WC-ADMIN/settings/payments';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageReturnsFalseForStoreApiPrettyPermalinks() {
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/checkout';

		$this->assertFalse( ContextHelper::isGatewaySettingsPage() );
	}

	public function testIsGatewaySettingsPageDoesNotRequireWpIsServingRestRequest() {
		// The function must NOT call wp_is_serving_rest_request() at all.
		// If it did, Brain\Monkey would complain because the function is not expected.
		$_SERVER['REQUEST_URI'] = '/wp-json/wc-admin/settings/payments/alma';

		$this->assertTrue( ContextHelper::isGatewaySettingsPage() );
	}
}


