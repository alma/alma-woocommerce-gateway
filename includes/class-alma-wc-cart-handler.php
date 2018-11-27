<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Cart_Handler {
	public function __construct() {
		if ( ! alma_wc_plugin()->settings->is_usable() ) {
			return;
		}

		$locale = get_locale();
		if ( $locale !== 'fr_FR' ) {
			Alma_WC_Logger::info( "Locale {$locale} not supported - Not displaying Alma" );

			return;
		}

		$currency = get_woocommerce_currency();
		if ( $currency !== 'EUR' ) {
			Alma_WC_Logger::info( "Currency {$currency} not supported - Not displaying Alma" );

			return;
		}

		if ( alma_wc_plugin()->settings->display_cart_eligibility ) {
			add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_eligibility' ) );
		}
	}

	/**
	 *  Display message below cart totals to indicate whether Alma is available or not
	 */
	public function display_cart_eligibility() {
		$eligibility_msg = alma_wc_plugin()->settings->cart_is_eligible_message;
		$logo_url        = alma_wc_plugin()->get_asset_url( 'images/tiny_logo.png' );

		try {
			$alma        = alma_wc_plugin()->get_alma_client();
			$eligibility = $alma->payments->eligibility( Alma_WC_Payment::from_cart() );
		} catch ( \Alma\RequestError $e ) {
			Alma_WC_Logger::error( 'Error checking payment eligibility: ' . $e->getMessage() );

			return;
		}

		if ( ! $eligibility->is_eligible ) {
			$eligibility_msg = alma_wc_plugin()->settings->cart_not_eligible_message;

			try {
				$merchant = $alma->merchants->me();
			} catch ( \Alma\RequestError $e ) {
				Alma_WC_Logger::error( 'Error fetching merchant information: ' . $e->getMessage() );
			}

			if ( isset( $merchant ) && $merchant ) {
				$cart       = new Alma_WC_Cart();
				$cart_total = $cart->get_total();
				$min_amount = $merchant->minimum_purchase_amount;
				$max_amount = $merchant->maximum_purchase_amount;

				if ( $cart_total < $min_amount || $cart_total > $max_amount ) {
					if ( $cart_total > $max_amount ) {
						$eligibility_msg .= '<br>' . sprintf( __( '(Maximum amount: %s)', ALMA_WC_TEXT_DOMAIN ), wc_price( alma_wc_price_from_cents( $max_amount ), array( 'decimals' => 0 ) ) );
					} else {
						$eligibility_msg .= '<br>' . sprintf( __( '(Minimum amount: %s)', ALMA_WC_TEXT_DOMAIN ), wc_price( alma_wc_price_from_cents( $min_amount ), array( 'decimals' => 0 ) ) );
					}
				}
			}
		}
		?>
        <div style="margin: 15px 0">
            <img src="<?php echo $logo_url; ?>"
                 style="width: initial !important; height: initial !important; border: none !important; vertical-align: middle"
                 alt="Alma"> <span style="text-transform: initial"><?php echo $eligibility_msg; ?></span>
        </div>
		<?php
	}
}
