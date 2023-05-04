<?php
/**
 * Alma_Tools_Helper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Alma_Logger;

/**
 * Alma_Tools_Helper.
 */
class Alma_Tools_Helper {


	/**
	 * The logger.
	 *
	 * @var Alma_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Alma_Logger();
	}

	/**
	 * Check if key match amount key format
	 *
	 * @param string $key As setting's key.
	 *
	 * @return boolean
	 */
	public static function is_amount_plan_key( $key ) {
		return preg_match( Alma_Constants_Helper::AMOUNT_PLAN_KEY_REGEX, $key ) > 0;
	}


	/**
	 * Converts a float price to its integer cents value, used by the API.
	 *
	 * @param float $price Price.
	 *
	 * @return integer
	 */
	public function alma_price_to_cents( $price ) {
		return (int) ( round( $price * 100 ) );
	}

	/**
	 * Format bps using default WooCommerce price renderer.
	 *
	 * @param int $bps Bps in cents.
	 *
	 * @return string
	 *
	 * @see wc_price()
	 */
	public static function alma_format_percent_from_bps( $bps ) {
		$decimal_separator  = wc_get_price_decimal_separator();
		$thousand_separator = wc_get_price_thousand_separator();
		$decimals           = wc_get_price_decimals();
		$price_format       = get_woocommerce_price_format();
		$negative           = $bps < 0;
		$bps                = number_format( self::alma_price_from_cents( $bps ), $decimals, $decimal_separator, $thousand_separator );
		$formatted_bps      = ( $negative ? '-' : '' ) . sprintf( $price_format, '<span class="woocommerce-Price-currencySymbol">&#37;</span>', $bps );

		return '<span class="woocommerce-Price-amount amount">' . $formatted_bps . '</span>';
	}

	/**
	 * Converts an integer price in cents to a float price in the used currency units.
	 *
	 * @param int $price Price.
	 *
	 * @return float
	 */
	public static function alma_price_from_cents( $price ) {
		return (float) ( $price / 100 );
	}

	/**
	 * Format price using default WooCommerce price renderer.
	 *
	 * @param int   $price Price in cents.
	 * @param array $args (default: array()).
	 *
	 * @return string
	 *
	 * @see wc_price()
	 */
	public static function alma_format_price_from_cents( $price, $args = array() ) {
		return wc_price( self::alma_price_from_cents( $price ), array_merge( array( 'currency' => 'EUR' ), $args ) );
	}

	/**
	 * Converts a string (e.g. 'yes' or 'no') to a bool.
	 *
	 * Taken from WooCommerce, to which it was added in version 3.0.0, and we need support for older WC versions.
	 *
	 * @param string $string String to convert.
	 *
	 * @return bool
	 */
	public static function alma_string_to_bool( $string ) {
		return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
	}



	/**
	 * Get webhook url
	 *
	 * @param string $webhook Webhook.
	 *
	 * @return string
	 */
	public function url_for_webhook( $webhook ) {
		return wc()->api_request_url( $webhook );
	}

	/**
	 * Get webhook action.
	 *
	 * @param string $webhook Webhook.
	 *
	 * @return string
	 */
	public static function action_for_webhook( $webhook ) {
		return "woocommerce_api_$webhook";
	}

	/**
	 * Force Check settings.
	 *
	 * @return bool
	 */
	public function check_currency() {
		$currency = get_woocommerce_currency();
		if ( 'EUR' !== $currency ) {
			$this->logger->warning(
				'Currency not supported - Not displaying by Alma.',
				array(
					'Currency' => $currency,
				)
			);
			return false;
		}

		return true;
	}

}
