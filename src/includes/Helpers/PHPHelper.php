<?php
/**
 * PHPHelper.
 *
 * @since 4.1.1
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PHPHelper
 */
class PHPHelper {
	/**
	 * Checks if the object or class has a property.
	 *
	 * @param object|string $object_or_class The class or object to check.
	 * @param string        $property The property to check.
	 *
	 * @codeCoverageIgnore
	 * @return bool
	 */
	public function property_exists( $object_or_class, $property ) {
		return property_exists( $object_or_class, $property );
	}


}
