/**
 * Checkout blocks page.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

// phpcs:ignoreFile

import {useEffect, useState} from '@wordpress/element';
import {Logo} from '@alma/react-components'
import {AlmaBlocks} from "./components/alma-blocks-component.tsx";
import '../css/alma-checkout-blocks.css'

(function ($) {

    const gateways = ['alma_pay_now',  'alma_in_page_pay_now', 'alma', 'alma_in_page', 'alma_pay_later', 'alma_in_page_pay_later', 'alma_pnx_plus_4'];
    var inPage = undefined;
    var hasInPage = false;
    var billingAddress = {};
    var shippingAddress = {};
    var customerNote = '';

    for (const gateway in gateways) {
        const settings = window.wc.wcSettings.getSetting(`${gateways[gateway]}_data`, null);

        if (!settings) {
            continue;
        }

        const label = window.wp.htmlEntities.decodeEntities(settings.title);
        const Label = props => {
            const {PaymentMethodLabel} = props.components;
            const icon = <Logo style={{width: 'auto', height: '1em'}} logo="alma-orange"/>
            const text = <div>{settings.title}</div>
            return <span className='paymentMethodLabel'>
                <PaymentMethodLabel text={text} icon={icon}/>
            </span>
        };

        function DisplayAlmaBlocks(props) {
            const [selectedFeePlan, setSelectedFeePlan] = useState(settings.default_plan)
            const {eventRegistration, emitResponse} = props;

            useEffect(() =>  {
                // removeEventListener('onCheckoutBeforeProcessing')
                billingAddress = props.billing.billingAddress
        
                if (props.shippingData.shippingAddress) {
                    shippingAddress = props.shippingData.shippingAddress
                }
                console.log("coucou", billingAddress,shippingAddress)
            }, [props]);

            if (!settings.is_in_page) {
                const {onPaymentSetup} = eventRegistration;
                useEffect(
                    () => {
                        const unsubscribe = onPaymentSetup(
                            async () => {
                                // Here we can do any processing we need, and then emit a response.
                                // For example, we might validate a custom field, or perform an AJAX request, and then emit a response indicating it is valid or not.
                                const nonceKey = `alma_checkout_nonce${settings.gateway_name}`;
                                const paymentMethodData = {
                                    [nonceKey]: `${settings.nonce_value}`,
                                    alma_fee_plan: selectedFeePlan,
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
                        onPaymentSetup,
                        selectedFeePlan
                    ]
                );
                return (
                    <AlmaBlocks settings={settings} selectedFeePlan={selectedFeePlan} setSelectedFeePlan={setSelectedFeePlan}/>
                )
            }

            if(settings.is_in_page ||(gateway === 'alma_in_page')){
                window.addEventListener(
                    'load',
                    (event) => {
                        function initializeInpage(settingsInPage) {
                            if (
                                inPage !== undefined
                                && document.getElementById('alma-embedded-iframe') !== null
                            ) {
                                inPage.unmount();
                            }

                            inPage = Alma.InPage.initialize(
                                {
                                    merchantId: settingsInPage.merchant_id,
                                    amountInCents: settingsInPage.amount_in_cents,
                                    installmentsCount: '1',
                                    selector: "#alma-inpage-alma_in_page",
                                    environment: settingsInPage.environment,
                                    locale: settingsInPage.locale,
                                }
                            );
                        }

                        function isAlmaInPageChecked() {
                            // verif that the paiment type method is in page.
                            return hasInPage
                        }

                        function add_loader() {
                            var loading = "<div class='loadingIndicator'><img src='https://cdn.almapay.com/img/animated-logo-a.svg' alt='Loading' /></div>";
                            $("body").append("<div class='alma-loader-wrapper'>" + loading + "</div>");
                        }

                        function cancel_order(orderId) {
                            var data = {
                                'action': 'alma_cancel_order_in_page',
                                'order_id': orderId
                            };

                            jQuery.post(ajax_object.ajax_url, JSON.stringify(data))
                        }


                        if (hasInPage) {
                            var settingsInPage = window.wc.wcSettings.getSetting('alma_in_page_data', null);
                            console.log(settingsInPage)

                            initializeInpage(settingsInPage);
                        }
                        
                        document.getElementsByClassName("wc-block-components-checkout-place-order-button")[0].addEventListener(
                            "click",
                            (event) => {
                                if (isAlmaInPageChecked()) {
                                    console.log($( 'wc-block-checkout__form' ).serializeArray(), 'OUI ICICICIOQJOSIDJ')
                                    event.stopPropagation()
                                    // customer_note + shipping_address +
                                    var data = {
                                        'action': 'alma_do_checkout_in_page',
                                        'fields': {
                                            'billing': billingAddress,
                                            'shipping': shippingAddress,
                                            'customer_note': customerNote,
                                            'alma_fee_plan': 'general_1_0_0',
                                            'alma_checkout_noncealma_in_page_pay_now': settingsInPage.nonce_value,
                                            'payment_method': 'alma_in_page_pay_now',
                                        },
                                        'alma_checkout_noncealma_in_page_pay_now': settingsInPage.nonce_value,
                                        'woocommerce-process-checkout-nonce': settingsInPage.woocommerce_process_checkout_nonce,
                                        'payment_method': 'alma_in_page_pay_now',
                                        'alma_fee_plan': 'general_1_0_0',
                                        'alma_fee_plan_in_page': 'general_1_0_0',
                                        'is_woo_block': true
                                    };
                                    console.log(JSON.stringify(data))
                                    // add_loader();

                                    // Create the payment id and order.
                                    jQuery.post(ajax_object.ajax_url, data)
                                        .done(
                                            function (response) {
                                                console.log("ici",response)
                                                var paymentId = response.data.payment_id;
                                                var orderId = response.data.order_id;

                                                // Start the payment.
                                                inPage.startPayment(
                                                    {
                                                        paymentId: paymentId,
                                                        onUserCloseModal: () => {
                                                            cancel_order(orderId);
                                                            $('.alma-loader-wrapper').remove();
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
                    }
                )
                return <>
                    <AlmaBlocks settings={settings} selectedFeePlan={selectedFeePlan} setSelectedFeePlan={setSelectedFeePlan}/>
                    <div id='alma-inpage-alma_in_page'></div>
                </>
            }
            // customerNote = props.customerNote
        }


        const Block_Gateway_Alma = {
            name: settings.gateway_name,
            label: <Label/>,
            content: <DisplayAlmaBlocks/>, // phpcs:ignore
            edit: <DisplayAlmaBlocks/>,  // phpcs:ignore
            placeOrderButtonLabel: settings.label_button,
            canMakePayment: () => true,
            ariaLabel: label
        };

        window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);

        if (settings.is_in_page) {
            hasInPage = true
        }
    }

})(jQuery);
