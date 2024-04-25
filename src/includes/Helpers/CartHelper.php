<?php
/**
 * CartHelper.
 *
 * @since 4.3.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Class CartHelper.
 */
class CartHelper {

	/**
	 * Helper global.
	 *
	 * @var ToolsHelper
	 */
	protected $tools_helper;

	/**
	 * Helper Session.
	 *
	 * @var SessionHelper
	 */
	protected $session_helper;


	/**
	 * Helper Version.
	 *
	 * @var VersionHelper
	 */
	protected $version_helper;


	/**
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 * @param ToolsHelper   $tools_helper The tool Helper.
	 * @param SessionHelper $session_helper The session Helper.
	 * @param VersionHelper $version_helper The version Helper.
	 */
	public function __construct( $tools_helper, $session_helper, $version_helper ) {
		$this->tools_helper   = $tools_helper;
		$this->session_helper = $session_helper;
		$this->version_helper = $version_helper;
	}

	/**
	 * Get cart total in cents.
	 *
	 * @return integer
	 * @see alma_price_to_cents()
	 * @see get_total_from_cart
	 */
	public function get_total_in_cents() {
		return $this->tools_helper->alma_price_to_cents( $this->get_total_from_cart() );
	}

	/**
	 * Gets total from wc cart depending on which wc version is running.
	 *
	 * @return float
	 */
	public function get_total_from_cart() {
		$cart = $this->get_cart();

		if ( ! $cart ) {
			return 0;
		}

		if ( version_compare( $this->version_helper->get_version(), '3.2.0', '<' ) ) {
			return $cart->total;
		}

		$total = $cart->get_total( null );

		$session       = $this->session_helper->get_session();
		$session_total = $session->get( 'cart_totals', null );

		if (
			(
				0 === $total
				|| '0' === $total
			)
			&& ! empty( $session_total['total'] )
		) {
			$total = $session_total['total'];
		}

		return $total;
	}

	/**
	 * Get Wc cart
	 *
	 * @codeCoverageIgnore
	 * @return \WC_Cart|null
	 */
	public function get_cart() {
		return wc()->cart;
	}
}
