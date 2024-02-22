/**
 * Checkout blocks page.
 *
 * @package Alma_Gateway_For_Woocommerce
 */
import {useEffect} from '@wordpress/element';

const gateways = [ 'alma_in_page_pay_now', 'alma', 'alma_pay_later', 'alma_pay_now', 'alma_pnx_plus_4', 'alma_pa'];
var inPage = undefined;
var hasInPage = false;

for (const gateway in gateways) {
	const settings = window.wc.wcSettings.getSetting( `${gateways[gateway]}_data`, null );

	if ( ! settings) {
		continue;
	}
	const label = window.wp.htmlEntities.decodeEntities( settings.title );
	const Content = (props) => {

		if (!settings.is_in_page) {
			const {eventRegistration, emitResponse} = props;
			const {onPaymentProcessing} = eventRegistration;
			useEffect(
				() => {
					const unsubscribe = onPaymentProcessing(
						async () => {
							// Here we can do any processing we need, and then emit a response.
							// For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
							const nonceKey = `alma_checkout_nonce${settings.gateway_name}`;
							const paymentMethodData = {
								[nonceKey]: `${settings.nonce_value}`,
								alma_fee_plan: 'general_3_0_0',
								payment_method: settings.gateway_name,
							}
							return {
								type: emitResponse.responseTypes.SUCCESS,
								meta: {
									paymentMethodData
								}
							};
						}
					);
					// Unsubscribes when this component is unmounted.
					return () => {
						unsubscribe();
					};
				},
				[
					emitResponse.responseTypes.ERROR,
					emitResponse.responseTypes.SUCCESS,
					onPaymentProcessing,
				]
			);


			return settings.description;
		}
		return <div id='alma-inpage-alma_in_page_pay_now'></div>;
	};
			

	const Block_Gateway_Alma = {
		name: settings.gateway_name,
		label: label,
		content: < Content /> ,
		edit: < Content /> ,
		placeOrderButtonLabel: settings.label_button,
		canMakePayment: () => true,
		ariaLabel: label
	};

	window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway_Alma );

	if (settings.is_in_page) {
		hasInPage = true
	}


}

window.addEventListener('load', (event) => {
    	if (hasInPage) {
    		initializeInpage();
    	}
})

function initializeInpage() {
	if (
		inPage !== undefined
		&& document.getElementById( 'alma-embedded-iframe' ) !== null
	) {
		inPage.unmount();
	}

console.log('icvi');
	inPage = Alma.InPage.initialize(
		{
			merchantId: 'merchant_11v4pR74zvoPaF0EldGQRpJo41fP268FSw',
			amountInCents: '10000',
			installmentsCount: '1',
			selector: "#alma-inpage-alma_in_page_pay_now",
			environment: 'TEST',
			locale: 'FR',
		}
	);
}