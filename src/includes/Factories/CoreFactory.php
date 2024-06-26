<?php
/**
 * CoreFactory.
 *
 * @since 5.4.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Factories
 * @namespace Alma\Woocommerce\Factories
 */

namespace Alma\Woocommerce\Factories;

use Automattic\WooCommerce\Admin\Overrides\ThemeUpgrader;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CoreFactory.
 */
class CoreFactory {

	/**
	 * Checks if the current post has any of given terms.
	 *
	 * @param array|int|string $term The term name/ term_id/ slug, or an array of them to check for. Default empty.
	 * @param string           $taxonomy Taxonomy name. Default empty.
	 * @param int|\WP_Post     $post Post to check. Defaults to the current post.
	 *
	 * @return bool
	 */
	public function has_term( $term = '', $taxonomy = '', $post = null ) {
		return has_term( $term, $taxonomy, $post );
	}

	/**
	 * Detect is admin mode.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return bool
	 */
	public function is_admin() {
		return is_admin();
	}
}
