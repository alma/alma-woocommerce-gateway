/**
 * Checkout page.
 *
 * @package Alma_Gateway_For_Woocommerce
 *
 * @since 4.4.0
 */

(function ($) {

	var data = {
		'action': 'inpage_payload_alma',
		'accept': value
	};

	jQuery.post( ajax_object.ajax_url, data )
		.done(
			function (response) {
				const inPage = Alma.InPage.initialize( data.payment_id );
				inPage.mount( "#alma-inpage" );
				inPage.startPayment();
			}
		)
		.fail(
			function (response) {

			}
		);
} )( jQuery );
