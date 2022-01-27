/**
 * Checkout page.
 *
 * @package Alma_WooCommerce_Gateway
 */

(function ($) {

	$( 'body' ).on(
		'change',
		'input[name="alma_fee_plan"]',
		function() {
			if ($( this ).prop( 'checked' ) == true) {
				$( this ).closest( 'li.wc_payment_method' ).attr( 'data-already-checked', $( this ).attr( 'id' ) );
			}
		}
	);

	$( 'body' ).on(
		'payment_method_selected',
		function(){
			var payment_method = $( '.woocommerce-checkout input[name="payment_method"]:checked' ).closest( 'li.wc_payment_method' );
			if (typeof payment_method.attr( 'data-already-checked' ) != 'undefined') {
				$( '#' + payment_method.attr( 'data-already-checked' ) ).prop( 'checked', true ).trigger( 'change' );
			} else {
				fee_plans = payment_method.find( 'input[name="alma_fee_plan"]' );
				fee_plans.first().prop( 'checked', true ).trigger( 'change' );
			}
		}
	);
} )( jQuery );
