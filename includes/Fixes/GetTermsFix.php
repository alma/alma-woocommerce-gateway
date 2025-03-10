<?php
/**
 * Get terms fix.
 *
 * @package Alma\Woocommerce\Fixes
 */

namespace Alma\Woocommerce\Fixes;

/**
 * Class GetTermsFix
 * Fix for get_terms function to be compatible with WordPress 4.4.0.
 */
class GetTermsFix {

	/**
	 * Get terms.
	 *
	 * @param array $args Arguments.
	 */
	public static function get_terms( $args ) {
		if ( defined( 'WP_VERSION' ) && version_compare( WP_VERSION, '4.5.0', '<' ) ) {
			// phpcs:disable
			 return get_terms(
				$args['taxonomy'],
				array_filter(
					$args,
					function ( $term ) {
						return 'taxonomy' !== $term;
					}
				)
			);
			// phpcs:enable
		} else {
			return get_terms( $args );
		}
	}
}
