/**
 * Checkout blocks page.
 *
 * @package Alma_Gateway_For_Woocommerce
 */
import {useEffect} from '@wordpress/element';

const gateways = ['alma', 'alma_pay_later', 'alma_pay_now', 'alma_pnx_plus_4'];

for (const gateway in gateways) {
    const settings = window.wc.wcSettings.getSetting(`${gateways[gateway]}_data`, null);
    console.log(settings);
    if (!settings) {
        continue;
    }
    const label = window.wp.htmlEntities.decodeEntities(settings.title);

    const Content = (props) => {
        const {eventRegistration, emitResponse} = props;
        const {onPaymentProcessing} = eventRegistration;
        useEffect(() => {
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
    };

    const Block_Gateway_Alma = {
        name: settings.gateway_name,
        label: label,
        content: <Content/>,
        edit: <Content/>,
        placeOrderButtonLabel: settings.label_button,
        canMakePayment: () => true,
        ariaLabel: label
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);
}
