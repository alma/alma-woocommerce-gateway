<?php
/**
 * CustomerHelperBuilder.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Builders/Helpers
 * * @namespace Alma\Woocommerce\Builders\Helpers
 */

namespace Alma\Woocommerce\Builders\Helpers;

use Alma\Woocommerce\Helpers\CustomerHelper;
use Alma\Woocommerce\Traits\BuilderTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CustomerHelperBuilder.
 */
class CustomerHelperBuilder {

	use BuilderTrait;

	/**
	 * Customer Helper.
	 *
	 * @return CustomerHelper
	 */
	public function get_instance() {
		return new CustomerHelper( $this->get_customer_factory() );
	}
}
