<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class Alma_WC_Cart {
	private $legacy = false;
	private $cart;

	public function __construct() {
		$this->legacy = version_compare( wc()->version, '3.2.0', '<' );
		$this->cart   = wc()->cart;
	}

	public function get_total() {
		if ( $this->legacy ) {
			return alma_wc_price_to_cents( $this->cart->total );
		} else {
			return alma_wc_price_to_cents( $this->cart->get_total( null ) );
		}
	}
}
