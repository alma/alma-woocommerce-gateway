<?php
/**
 * Alma cart
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Model_Cart
 */
class Alma_WC_Model_Cart {
    /**
     * Cart
     *
     * @var WC_Cart|null
     */
    private $cart;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct() {
        $this->cart = WC()->cart;
    }

    /**
     * Get cart total in cents.
     *
     * @return integer
     * @see alma_wc_price_to_cents()
     * @see get_total_from_wc_cart
     */
    public function get_total_in_cents() {
        return alma_wc_price_to_cents( $this->get_total_from_wc_cart() );
    }

    /**
     * Gets total from wc cart depending on which wc version is running.
     *
     * @return float
     */
    protected function get_total_from_wc_cart() {
        if ( ! $this->cart ) {
            return 0;
        }
        if ( version_compare( WC()->version, '3.2.0', '<' ) ) {
            return $this->cart->total;
        }

        return $this->cart->get_total( null );
    }
}
