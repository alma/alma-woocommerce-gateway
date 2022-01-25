/**
 * Checkout page.
 *
 * @package Alma_WooCommerce_Gateway
 */

(function ($) {

	$( 'body' ).on('change', 'input[name="alma_fee_plan"]', function() {
		$(this).closest('li.wc_payment_method').attr('data-already-checked', $(this).attr('id'));
	});


	$( 'body' ).on(
		'payment_method_selected',
		function(){
			// $( 'input[name="alma_fee_plan"]' ).prop( 'checked', false ).trigger( 'change' );
			// var selectedPaymentMethod = $('.woocommerce-checkout input[name="payment_method"]:checked');

			var trigger_action = true;
			var payment_method = $('.woocommerce-checkout input[name="payment_method"]:checked').closest('li.wc_payment_method');
			if (typeof payment_method.attr('data-already-checked') != 'undefined') {
				$('#'+payment_method.attr('data-already-checked')).prop('checked', true).trigger('change', 'click');
				return;
			}

			var fee_plans = payment_method.find('input[name="alma_fee_plan"]');

			var trigger_action   = true;
			var fee_plan_default = null;
			fee_plans.each(function(){
				console.log('one fee_plan');
				if ($(this).attr('data-default') == '1') {
					fee_plan_default = $(this).attr('id');
					console.log('fee_plan_default = '+$(this).attr('id'));
				}
			});

			if (fee_plan_default !== null) {
				$('#'+fee_plan_default).prop('checked', true).trigger('change', 'click');
				payment_method.attr('data-already-checked', fee_plan_default);
			}
			else {
				console.log('first');
				fee_plans.first().prop('checked', true).trigger('change', 'click');
			}

			// console.log('-----------------');
			// console.log($('.woocommerce-checkout input[name="payment_method"]:checked').length);
			// console.log($('.woocommerce-checkout input[name="payment_method"]:checked').closest('li.wc_payment_method').length);
			// console.log($('.woocommerce-checkout input[name="payment_method"]:checked').closest('li.wc_payment_method').find('input[name="alma_fee_plan"]').length);
			// console.log($('.woocommerce-checkout input[name="payment_method"]:checked').closest('li.wc_payment_method').find('input[name="alma_fee_plan"]').first().length);

		}
	);



} )( jQuery );
