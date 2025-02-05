<?php
/**
 * PHPFactory.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Factories
 * @namespace Alma\Woocommerce\Factories
 */

namespace Alma\Woocommerce\Factories;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class PHPFactory.
 */
class PHPFactory {

	/**
	 * Does the method exists ?
	 *
	 * @param object $class The class.
	 * @param string $method The methods.
	 *
	 * @return bool
	 */
	public function method_exists( $class, $method ) {

		return method_exists( $class, $method );
	}
}
