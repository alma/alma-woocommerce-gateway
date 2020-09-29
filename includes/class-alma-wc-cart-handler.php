<?php
/**
 * Alma payments pluging for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' );
}

class Alma_WC_Cart_Handler {
	private $logger;

	public function __construct() {
		$this->logger = new Alma_WC_Logger();

		if ( ! alma_wc_plugin()->settings->is_usable() ) {
			return;
		}

		$locale = get_locale();
		if ( $locale !== 'fr_FR' ) {
			$this->logger->info( "Locale {$locale} not supported - Not displaying Alma" );

			return;
		}

		$currency = get_woocommerce_currency();
		if ( $currency !== 'EUR' ) {
			$this->logger->info( "Currency {$currency} not supported - Not displaying Alma" );

			return;
		}

		if ( 'yes' == alma_wc_plugin()->settings->display_cart_eligibility ) {
			add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'display_cart_eligibility' ) );
		}
	}

	/**
	 *  Display message below cart totals to indicate whether Alma is available or not
	 */
	public function display_cart_eligibility() {
		$eligibility_msg       = alma_wc_plugin()->settings->cart_is_eligible_message;
		$logo_url              = alma_wc_plugin()->get_asset_url( 'images/alma_logo.svg' );
		$skip_eligibility_call = false;

		if (
				isset( alma_wc_plugin()->settings->excluded_products_list ) &&
				is_array( alma_wc_plugin()->settings->excluded_products_list ) &&
				count( alma_wc_plugin()->settings->excluded_products_list ) > 0
		) {
			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				$product = $cart_item['data'];

				foreach ( alma_wc_plugin()->settings->excluded_products_list as $category_slug ) {
					if ( has_term( $category_slug, 'product_cat', $product->get_id() ) ) {
						$skip_eligibility_call = true;
						$eligibility_msg       = alma_wc_plugin()->settings->cart_not_eligible_message_gift_cards;
					}
				}
			}
		}

		if ( ! $skip_eligibility_call ) {
			try {
				$alma        = alma_wc_plugin()->get_alma_client();
				$eligibility = $alma->payments->eligibility( Alma_WC_Payment::from_cart() );
			} catch ( \Alma\API\RequestError $e ) {
				$this->logger->error( 'Error checking payment eligibility: ' . $e->getMessage() );

				return;
			}

			if ( ! $eligibility->isEligible ) {
				$eligibility_msg = alma_wc_plugin()->settings->cart_not_eligible_message;

				$cart       = new Alma_WC_Cart();
				$cart_total = $cart->get_total();
				$min_amount = $eligibility->constraints['purchase_amount']['minimum'];
				$max_amount = $eligibility->constraints['purchase_amount']['maximum'];

				// TODO: The PHP client should allow us to check whether the Eligibility call resulted in an error
				if ( $max_amount === null || (int) $max_amount === 0 ) {
					return;
				}

				if ( $cart_total < $min_amount || $cart_total > $max_amount ) {
					if ( $cart_total > $max_amount ) {
						$eligibility_msg .= '<br>' . sprintf( __( '(Maximum amount: %s)', 'alma-woocommerce-gateway' ), wc_price( alma_wc_price_from_cents( $max_amount ), array( 'decimals' => 0 ) ) );
					} else {
						$eligibility_msg .= '<br>' . sprintf( __( '(Minimum amount: %s)', 'alma-woocommerce-gateway' ), wc_price( alma_wc_price_from_cents( $min_amount ), array( 'decimals' => 0 ) ) );
					}
				}
			}
		}
		?>
		<div class="alma--eligibility-msg" style="margin: 15px 0">
			<img src="<?php echo $logo_url; ?>"
				 style="width: auto !important; height: 25px !important; border: none !important; vertical-align: middle"
				 alt="Alma"> <span style="text-transform: initial"><?php echo $eligibility_msg; ?></span>
		</div>
		<?php
	}
}
