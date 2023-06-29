/**
 * Checkout page.
 *
 * @package Alma_Gateway_For_Woocommerce
 */

(function ($) {

	$( 'body' ).on(
		'updated_checkout',
		function() {
			var selectedPaymentMethod = $( '.woocommerce-checkout input[name="payment_method"]:checked' );
			var fee_plans             = selectedPaymentMethod.find( 'input[name="alma_fee_plan"]' );
			fee_plans.first().prop( 'checked', true ).trigger( 'change' );
		}
	);

	$( 'body' ).on(
		'click',
		'a[class="woocommerce-remove-coupon"]',
		function(){
			location.reload();
		}
	);

	$( 'body' ).on(
		'change',
		'input[name="alma_fee_plan"]',
		function() {
			var icons = {
				header: "alma-angle alma-angle-right",
				activeHeader: "alma-angle alma-angle-down"
			};
			jQuery( "#alma_plans_accordion" ).accordion(
				{
					collapsible: true,
					header: "h5",
					heightStyle: "content",
					icons: icons
				}
			);

			if ( $( this ).prop( 'checked' ) ) {
				$( this ).closest( 'li.wc_payment_method' ).attr( 'data-already-checked', $( this ).attr( 'id' ) );
				jQuery( "#alma-checkout-plan-details" ).insertAfter( $( this ).parent()[0].lastElementChild )

			}
		}
	);

	$( 'body' ).on(
		'updated_checkout',
		function () {
			var radios = $( '.ui-accordion-header' ).next( "div" ).find( "input:visible" );
			radios.first().prop( "checked", true ).trigger( 'change' );
		}
	);

	$( 'body' ).on(
		'click',
		'.payment_method_alma',
		function (event) {
			var target = $( event.target );
			if (target.is( "h5:first, label" )) {
				var firstVisibleDivAfterH5 = $( 'h5 + div:first' );
				var firstVisibleInputInDiv = firstVisibleDivAfterH5.find( "input:first" );
				if (firstVisibleInputInDiv.length && ! firstVisibleInputInDiv.prop( "checked" )) {
					firstVisibleInputInDiv.prop( "checked", true ).trigger( 'change' );
				}
			}
		}
	);

	$( 'body' ).on(
		'click',
		'.ui-accordion-header',
		function () {
			if ($( this ).attr( "aria-selected" ) === "true") {
				var radios = $( this ).next( "div" ).find( "input" );
				radios.first().prop( "checked", true ).trigger( 'change' );
			}
		}
	);

	$( 'body' ).on(
		'payment_method_selected',
		function(){
			render_alma_methods();
		}
	);

	function render_alma_methods()
	{
		var payment_method = $( '.woocommerce-checkout input[name="payment_method"]:checked' ).closest( 'li.wc_payment_method' );
		if (typeof payment_method.attr( 'data-already-checked' ) != 'undefined') {
			$( '#' + payment_method.attr( 'data-already-checked' ) ).prop( 'checked', true ).trigger( 'change' );
		} else {
			var fee_plans        = payment_method.find( 'input[name="alma_fee_plan"]' );
			var fee_plan_checked = false;
			fee_plans.each(
				function(){
					if (typeof $( this ).attr( 'data-default' ) !== typeof undefined && $( this ).attr( 'data-default' ) === '1') {
						$( this ).prop( 'checked', true ).trigger( 'change' );
						fee_plan_checked = true;
						return false;
					}
				}
			);
			if (fee_plan_checked === false) {
				fee_plans.first().prop( 'checked', true ).trigger( 'change' );
			}
		}
	}
} )( jQuery );
