/**
 * Checkout page.
 *
 * @package Alma_WooCommerce_Gateway
 */

(function ($) {

	$( 'body' ).on(
		'payment_method_selected',
		function(){
			// $( 'input[name="alma_fee_plan"]' ).prop( 'checked', false ).trigger( 'change' );
			// console.log('payment_method_selected');
			// var selectedPaymentMethod = $('.woocommerce-checkout input[name="payment_method"]:checked').attr('id');
		}
	);

} )( jQuery );
