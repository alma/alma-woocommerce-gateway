<?php
/**
 * ToolsHelper.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\AlmaLogger;

/**
 * ToolsHelper.
 */
class ToolsHelper {


	/**
	 * The logger.
	 *
	 * @var AlmaLogger
	 */
	protected $logger;

	/**
	 * Price Helper.
	 *
	 * @var PriceHelper
	 */
	protected $price_helper;


	/**
	 * Currency Helper.
	 *
	 * @var CurrencyHelper
	 */
	protected $currency_helper;

	/**
	 * Constructor.
	 *
	 * @param AlmaLogger     $logger  The logger.
	 * @param PriceHelper    $price_helper The price helper.
	 * @param  CurrencyHelper $currency_helper  The currency helper.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $logger, $price_helper, $currency_helper ) {
		$this->logger          = $logger;
		$this->price_helper    = $price_helper;
		$this->currency_helper = $currency_helper;
	}

	/**
	 * Check if key match amount key format
	 *
	 * @param string $key As setting's key.
	 *
	 * @return boolean
	 */
	public function is_amount_plan_key( $key ) {
		return preg_match( ConstantsHelper::AMOUNT_PLAN_KEY_REGEX, $key ) > 0;
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
	public function alma_format_percent_from_bps( $bps ) {
		$bps = number_format(
			$this->alma_price_from_cents( $bps ),
			$this->price_helper->get_woo_decimals(),
			$this->price_helper->get_woo_decimal_separator(),
			$this->price_helper->get_woo_thousand_separator()
		);

		$formatted_bps = sprintf(
			$this->price_helper->get_woo_format(),
			'<span class="woocommerce-Price-currencySymbol">&#37;</span>',
			$bps
		);

		return sprintf( '<span class="woocommerce-Price-amount amount">%s</span>', $formatted_bps );
	}

	/**
	 * Converts an integer price in cents to a float price in the used currency units.
	 *
	 * @param int $price Price.
	 *
	 * @return float
	 */
	public function alma_price_from_cents( $price ) {
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
	public function alma_format_price_from_cents( $price, $args = array() ) {
		return wc_price( $this->alma_price_from_cents( $price ), array_merge( array( 'currency' => 'EUR' ), $args ) );
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
		$currency = $this->currency_helper->get_currency();

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
