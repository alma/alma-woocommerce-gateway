/**
 * Checkout page.
 *
 * @since 5.0.0
 *
 * @param global alma_iframe_params iframe params.
 *
 * @package Alma_Gateway_For_Woocommerce
 */

(function ($) {
	var regex  = /^general_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})/g;
	var inPage = undefined;

	$( 'body' ).on(
		'updated_checkout',
		function (event) {
			if (isAlmaInPageChecked()) {
				$( "input[type='radio'][name='alma_fee_plan_in_page']:checked" ).click();
			}
		}
	);

	$( 'body' ).on(
		'click change',
		'input[name="payment_method"]',
		function (event) {
			if (isAlmaInPageChecked()) {
				feePlanChecked = $( "input[type='radio'][name='alma_fee_plan_in_page']:checked" ).val();
				render_installments( feePlanChecked , $( "input[type='radio'][name='alma_fee_plan_in_page']:checked" ) );
			}
		}
	);

	$( 'body' ).on(
		'click',
		'.alma_fee_plan_in_page',
		function (event) {
			render_installments( $( this ).prop( 'value' ) ,  $( this ) );
		}
	);

	function render_installments(value, checkbox)
	{
		payment_method = $( '.woocommerce-checkout input[name="payment_method"]:checked' );
		payment_value  = payment_method.attr( 'value' );

		matches = value.matchAll( regex );

		installment = null;

		for (const match of matches) {
			installment    = match[1];
			deferredDays   = match[2];
			deferredMonths = match[3];
		}

		if (null !== installment) {
			if (
				inPage !== undefined
				&& document.getElementById( 'alma-embedded-iframe' ) !== null
			) {
				inPage.unmount();
			}

			thousandSeparator = checkbox.data( 'settings-thousand-separator' )
			decimalSeparator  = checkbox.data( 'settings-decimal-separator' );
			nbDecimals        = checkbox.data( 'settings-nb-decimals' );

			amount = document.getElementsByClassName( "order-total" )["0"].getElementsByClassName( "woocommerce-Price-amount amount" )["0"].innerText.replace( /[^0-9]/g, '' );

			var amount = amount.replace( ' ', '' )
				.replace( thousandSeparator, '' )
				.replace( decimalSeparator, ',' )
				.replace( /[^\d.]/g, '' )

			switch (nbDecimals) {
				case 0:
					amount = amount * 100;
					break;
				case 1:
					amount = amount * 10;
					break;
			}

			inPage = Alma.InPage.initialize(
				{
					merchantId: alma_iframe_params.merchant_id,
					amountInCents: amount,
					installmentsCount: installment,
					deferredDays: deferredDays,
					deferredMonths: deferredMonths,
					selector: "#alma-inpage-" + payment_value,
					environment: alma_iframe_params.environment,
					locale: alma_iframe_params.locale,
				}
			);
		}
	}

	function isAlmaInPageChecked() {
		return $( '#payment_method_alma_in_page' ).is( ':checked' )
			|| $( '#payment_method_alma_in_page_pay_now' ).is( ':checked' )
			|| $( '#payment_method_alma_in_page_pay_later' ).is( ':checked' )
			|| $( '#payment_method_alma_in_page_pnx_plus_4' ).is( ':checked' );
	}

	$( 'body' ).on(
		'click',
		'button[name="woocommerce_checkout_place_order"]',
		function(evt) {

			if (isAlmaInPageChecked()) {
				evt.preventDefault();
				feePlanChecked = $( "input[type='radio'][name='alma_fee_plan_in_page']:checked" ).val();

				var data = {
					'action': 'alma_do_checkout_in_page',
					'fields': $( 'form.checkout' ).serializeArray(),
					'alma_fee_plan_in_page': feePlanChecked
				};

				add_loader();

				// Create the payment id and order.
				jQuery.post( ajax_object.ajax_url, data )
					.done(
						function (response) {
							var dataReturn = {
								'action': 'alma_return_checkout_in_page',
								'alma_fee_plan_in_page': feePlanChecked
							};

							paymentId = response.data.payment_id;
							orderId   = response.data.order_id;

							// Start the payment.
							inPage.startPayment(
								{
									paymentId:paymentId,
									onUserCloseModal: () => {
										cancel_order( orderId );
										$( '.alma-loader-wrapper' ).remove();
									}
								}
							);

						}
					)
				.fail(
					function (response) {
						location.reload();
					}
				);
			}
		}
	);

	function add_loader()
	{
		var loading = "<div class='loadingIndicator'><img src='https://cdn.almapay.com/img/animated-logo-a.svg' alt='Loading' /></div>";
		$( "body" ).append( "<div class='alma-loader-wrapper'>" + loading + "</div>" );
	}

	function cancel_order(orderId)
	{
		var data = {
			'action': 'alma_cancel_order_in_page',
			'order_id': orderId
		};

		jQuery.post( ajax_object.ajax_url, data )
	}

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
			var fee_plans        = payment_method.find( 'input[name="alma_fee_plan_in_page"]' );
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
