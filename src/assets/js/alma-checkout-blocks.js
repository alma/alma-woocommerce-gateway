/**
 * Checkout blocks page.
 *
 * @package Alma_Gateway_For_Woocommerce
 */
import { useEffect } from '@wordpress/element';

const settings = window.wc.wcSettings.getSetting('alma_data', {});
console.log(settings);
const label = window.wp.htmlEntities.decodeEntities(settings.title);

const Content = (props) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentProcessing} = eventRegistration;
    useEffect(() => {
            const unsubscribe = onPaymentProcessing(
                async () => {
                    // Here we can do any processing we need, and then emit a response.
                    // For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
                    const myGatewayCustomData = '12345';
                    const customDataIsValid = !!myGatewayCustomData.length;
                    if (customDataIsValid) {
                        return {
                            type: emitResponse.responseTypes.SUCCESS,
                            meta: {
                                paymentMethodData: {
                                    myGatewayCustomData,
                                },
                            },
                        };
                    }
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'There was an error',
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
    return decodeEntities(settings.description || '');
};

const Block_Gateway_Alma = {
    name: settings.gateway_name,
    label: label,
    content: <Content />,
    edit: <Content />,
    placeOrderButtonLabel: settings.label_button,
    canMakePayment: () => true,
    ariaLabel: label
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);
