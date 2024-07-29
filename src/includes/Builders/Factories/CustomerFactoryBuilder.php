<?php
/**
 * CustomerFactoryBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Factories
 * @namespace Alma\Woocommerce\Builders\Factories
 */

namespace Alma\Woocommerce\Builders\Factories;

use Alma\Woocommerce\Factories\CustomerFactory;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CustomerFactoryBuilder.
 */
class CustomerFactoryBuilder {

	use BuilderTrait;

	/**
	 * Customer Factory.
	 *
	 * @return CustomerFactory
	 */
	public function get_instance() {
		return new CustomerFactory(
			$this->get_php_factory()
		);
	}
}
