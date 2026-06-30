<?php

namespace Alma\Gateway\Tests\Unit;

use Alma\Gateway\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Guard rail: every place the version is declared must stay aligned.
 *
 * The runtime version (Plugin::ALMA_GATEWAY_PLUGIN_VERSION) is derived from the
 * plugin header, but the "Stable tag" entries in readme.txt / README.md are
 * WordPress.org requirements read as plain text and could still drift on a
 * release. This test fails as soon as one of them is out of sync, whatever the
 * cause (see ECOM-4303, where Plugin.php was left at 6.3.0 in the 6.4.0 release).
 */
class VersionConsistencyTest extends TestCase {

	/**
	 * @dataProvider versionSourceProvider
	 */
	public function testAllVersionSourcesMatchTheRuntimeVersion( $label, $relative_path, $pattern ) {
		$contents = file_get_contents( ABSPATH . $relative_path );
		$this->assertIsString( $contents, "Cannot read $relative_path" );

		$this->assertSame( 1, preg_match( $pattern, $contents, $matches ), "No version found in $relative_path" );

		$this->assertSame(
			Plugin::ALMA_GATEWAY_PLUGIN_VERSION,
			$matches[1],
			sprintf(
				'%s (%s) is out of sync with Plugin::ALMA_GATEWAY_PLUGIN_VERSION (%s).',
				$label,
				$matches[1],
				Plugin::ALMA_GATEWAY_PLUGIN_VERSION
			)
		);
	}

	public function versionSourceProvider() {
		return array(
			'plugin header "Version:"'  => array(
				'plugin header "Version:"',
				'alma-gateway-for-woocommerce.php',
				'/^\s*\*\s*Version:\s*([0-9.]+)/m',
			),
			'readme.txt "Stable tag"'   => array(
				'readme.txt "Stable tag"',
				'readme.txt',
				'/^[-\s]*Stable tag:\s*([0-9.]+)/m',
			),
			'README.md "Stable tag"'    => array(
				'README.md "Stable tag"',
				'README.md',
				'/^[-\s]*Stable tag:\s*([0-9.]+)/m',
			),
		);
	}
}