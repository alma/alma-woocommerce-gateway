/**
 * Checkout page.
 *
 * global alma_iframe_params
 * global alma_iframe_paiement

 * @package Alma_Gateway_For_Woocommerce
 */

(function ($) {
	var regex =/^general_([0-9]+)_[0-9]+_[0-9]+/g;
	$( 'body' ).on(
		'updated_checkout',
		function (event) {
			if(isAlmaInPageChecked()) {
				feePlanChecked = $("input[type='radio'][name='alma_fee_plan_in_page']:checked").val();
				render_installments(feePlanChecked);
			}
		}
	);


	$( 'body' ).on(
		'click change',
		'input[name="payment_method"]',
		function (event) {
			if(isAlmaInPageChecked()) {
				feePlanChecked = $("input[type='radio'][name='alma_fee_plan_in_page']:checked").val();
				render_installments(feePlanChecked);
			}
		}
	);

	$( 'body' ).on(
		'click',
		'.alma_fee_plan_in_page',
		function (event) {
			render_installments($( this ).prop('value'));
		}
	);

	function render_installments(value)
	{
		matches =  value.matchAll(regex);

		for (const match of matches) {
			installment = match[1];
		}

		inPage = Alma.InPage.initialize({
			merchantId: alma_iframe_params.merchant_id,
			amountInCents: alma_iframe_params.amount_in_cents,
			installmentsCount: installment, //
			selector: "#alma-inpage",

			// Optionals:
			environment: "TEST", // @todo
			locale: "FR", //@todo


		});
	}

	function isAlmaInPageChecked()
	{
		return $('#payment_method_alma_in_page').is(':checked');
	}

	$( 'body' ).on(
		'click',
		'button[name="woocommerce_checkout_place_order"]',
		function(evt) {

			if(isAlmaInPageChecked()) {
				evt.preventDefault();
				feePlanChecked = $("input[type='radio'][name='alma_fee_plan_in_page']:checked").val();

				var data = {
					'action': 'alma_do_checkout_in_page',
					'fields': $('form.checkout').serializeArray(),
					'alma_fee_plan_in_page': feePlanChecked
				};


				jQuery.post(ajax_object.ajax_url, data)
					.done(function (response) {
						var dataReturn =   {
							'action': 'alma_return_checkout_in_page',
							'alma_fee_plan_in_page': feePlanChecked
						};

						paymentId = response.data.payment_id;

						inPage.startPayment({
							paymentId:paymentId,
							onPaymentSucceeded: () => {
								jQuery.post(ajax_object.ajax_url + '?pid=' + paymentId, dataReturn)
									.done(function (response) {
										$(location).prop('href', response.data.url)
									})
									.fail(function (response) {
										location.reload();
									});
							},
							onPaymentRejected: () => {
								console.log("rejected");
								// @todo cancel order + message
							},
							onUserCloseModal: () => {
								console.log("user closed modal");
								// @todo cancel order
							}
						});



					})
					.fail(function (response) {
						console.log(response);
						console.log('fail')
						// @todo
					});
			}
		}
	);

} )( jQuery );
