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

use Alma\Woocommerce\Factories\CartFactory;
use Alma\Woocommerce\Factories\SessionFactory;
use Alma\Woocommerce\Factories\VersionFactory;

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
	 * @var SessionFactory
	 */
	protected $session_factory;


	/**
	 * Factory Version.
	 *
	 * @var VersionFactory
	 */
	protected $version_factory;

	/**
	 * Factory Cart.
	 *
	 * @var CartFactory
	 */
	protected $cart_factory;


	/**
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param ToolsHelper    $tools_helper The tool Helper.
	 * @param SessionFactory $session_factory The session Helper.
	 * @param VersionFactory $version_factory The version Helper.
	 * @param CartFactory    $cart_factory The cart Helper.
	 */
	public function __construct( $tools_helper, $session_factory, $version_factory, $cart_factory ) {
		$this->tools_helper    = $tools_helper;
		$this->session_factory = $session_factory;
		$this->version_factory = $version_factory;
		$this->cart_factory    = $cart_factory;
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
		$cart = $this->cart_factory->get_cart();

		if ( ! $cart ) {
			return 0;
		}

		if ( version_compare( $this->version_factory->get_version(), '3.2.0', '<' ) ) {
			return $cart->total;
		}

		$total = $cart->get_total( null );

		$session       = $this->session_factory->get_session();
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
}
