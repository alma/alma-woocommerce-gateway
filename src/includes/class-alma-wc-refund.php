<?php
/**
 * Alma refund
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Refund
 */
class Alma_WC_Refund {

	const FOO = 'bar';

	/**
	 * Logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;

	/**
	 * Logger
	 *
	 * @var Alma_WC_Share_Of_Checkout_Helper
	 */
	private $refund_helper;

	/**
	 * __construct.
	 */
	public function __construct() {
		$this->logger         = new Alma_WC_Logger();
		$this->refund_helper = new Alma_WC_Refund_Helper()_Helper();
	}
}
