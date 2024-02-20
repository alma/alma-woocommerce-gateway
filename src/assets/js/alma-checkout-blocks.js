/**
 * Checkout blocks page.
 *
 * @package Alma_Gateway_For_Woocommerce
 */

const settings = window.wc.wcSettings.getSetting( 'alma_data', {} );
console.log(settings);
	const label   = window.wp.htmlEntities.decodeEntities( settings.title );
	const Content = () => {
		return window.wp.htmlEntities.decodeEntities( settings.description );
	};

	const Block_Gateway = {
		name: 'alma',
		label: label,
		content: Object( window.wp.element.createElement )( Content, null ),
		edit: Object( window.wp.element.createElement )( Content, null ),
		placeOrderButtonLabel: 'Pay with Alma',
		canMakePayment: () => true,
		ariaLabel: label
	};

	window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );
