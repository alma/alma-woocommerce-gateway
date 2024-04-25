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
import {select} from '@wordpress/data';
import {Logo} from '@alma/react-components';
import {AlmaBlocks} from "./components/alma-blocks-component.tsx";
import '../css/alma-checkout-blocks.css';

(function ($) {
    const gateways = ['alma_pay_now', 'alma_in_page_pay_now', 'alma', 'alma_in_page', 'alma_pay_later', 'alma_in_page_pay_later', 'alma_pnx_plus_4'];
    var inPage = undefined;
    var propsData = null;
    var globalSelectedFeePlan = null;

    $.each(gateways, function (index, gateway) {
            const settings = window.wc.wcSettings.getSetting(`${gateway}_data`, null);

            if (!settings) {
                return
            }

            const label = window.wp.htmlEntities.decodeEntities(settings.title);
            const Label = props => {
                const {PaymentMethodLabel} = props.components;
                const icon = <Logo style={{width: 'auto', height: '1em'}} logo="alma-orange"/>
                const text = <div>{settings.title}</div>
                return <span className='paymentMethodLabel'>
                <PaymentMethodLabel text={text} icon={icon}/>
            </span>
            }

            function DisplayAlmaBlocks(props) {
                const {CART_STORE_KEY} = window.wc.wcBlocksData
                const cartStore = select(CART_STORE_KEY)
                const {total_price} = (cartStore.getCartTotals())
                const [selectedFeePlan, setSelectedFeePlan] = useState(settings.default_plan)
                const {eventRegistration, emitResponse} = props;
                const {
                    onCheckoutValidationBeforeProcessing
                } = eventRegistration;
                propsData = props

                useEffect(() => {
                    globalSelectedFeePlan = selectedFeePlan;
                }, [selectedFeePlan]);

                useEffect(() => {
                    const unsubscribe = onCheckoutValidationBeforeProcessing(() => {
                        return true
                    });
                    return unsubscribe;
                }, [onCheckoutValidationBeforeProcessing
                ]);


                // There cannot be two iframes in the same page, so this is the function to unmount it
                function initializeInpage(settingsInPage) {
                    if (
                        inPage !== undefined
                        && document.getElementById('alma-embedded-iframe') !== null
                    ) {
                        inPage.unmount();
                    }

                    inPage = Alma.InPage.initialize({
                        merchantId: settingsInPage.merchant_id,
                        amountInCents: total_price,
                        installmentsCount: settings.plans[selectedFeePlan].installmentsCount,
                        selector: "#alma-inpage-alma_in_page",
                        deferredDays: settings.plans[selectedFeePlan].deferredDays,
                        deferredMonths: settings.plans[selectedFeePlan].deferredMonths,
                        environment: settingsInPage.environment,
                        locale: settingsInPage.locale,
                    });
                }

                // Each time the settings change, we need to unmout the iframe and remount it with the new settings
                useEffect(() => {
                    if (
                        settings.gateway_name === 'alma_in_page_pay_now'
                        || settings.gateway_name === 'alma_in_page_pay_later'
                        || settings.gateway_name === 'alma_in_page'
                    ) {
                        initializeInpage(settings)
                    }
                }, [settings, selectedFeePlan, total_price])

                if (
                    settings.gateway_name === 'alma_in_page_pay_now'
                    || settings.gateway_name === 'alma_in_page_pay_later'
                    || settings.gateway_name === 'alma_in_page'
                ) {
                    window.addEventListener(
                        'load',
                        (event) => {
                            addActionToPaymentButton()
                        }
                    )
                    return <>
                        <AlmaBlocks hasInPage={settings.is_in_page} totalPrice={total_price} settings={settings}
                                    selectedFeePlan={selectedFeePlan}
                                    setSelectedFeePlan={setSelectedFeePlan}/>
                        <div id='alma-inpage-alma_in_page'></div>
                    </>
                } else {
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
                        <AlmaBlocks hasInPage={settings.is_in_page} totalPrice={total_price} settings={settings}
                                    selectedFeePlan={selectedFeePlan}
                                    setSelectedFeePlan={setSelectedFeePlan}/>

                    )
                }
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
        }
    );

    $('body').on(
        'load',
        function () {
            document.getElementsByClassName("wc-block-components-checkout-place-order-button")[0].removeEventListener("click", addActionToPaymentButtonListener);
            addActionToPaymentButton()
        }
    );
    $('body').on(
        'change',
        'input[type=\'radio\'][name=\'radio-control-wc-payment-method-options\']',
        function () {
            document.getElementsByClassName("wc-block-components-checkout-place-order-button")[0].removeEventListener("click", addActionToPaymentButtonListener);
            addActionToPaymentButton()
        }
    );

    const addActionToPaymentButton = () => {
        document.getElementsByClassName("wc-block-components-checkout-place-order-button")[0].addEventListener(
            "click",
            addActionToPaymentButtonListener
        );
    }

    const addActionToPaymentButtonListener = (event) => {
        const {CHECKOUT_STORE_KEY} = window.wc.wcBlocksData
        const store = select(CHECKOUT_STORE_KEY);
        const dataTest = {
            hasError: store.hasError(),
            redirectUrl: store.getRedirectUrl(),
            isProcessing: store.isProcessing(),
            isBeforeProcessing: store.isBeforeProcessing(),
            isComplete: store.isComplete(),
            orderNotes: store.getOrderNotes(),
            shouldCreateAccount: store.getShouldCreateAccount(),
            extensionData: store.getExtensionData(),
            customerId: store.getCustomerId(),
        };

        const gateway = $("input[type='radio'][name='radio-control-wc-payment-method-options']:checked").val();

        const settings = window.wc.wcSettings.getSetting(`${gateway}_data`, null);

        const almaCheckoutNonce = `alma_checkout_nonce${settings.gateway_name}`;

        if (
            settings.gateway_name === 'alma_in_page_pay_now'
            || settings.gateway_name === 'alma_in_page_pay_later'
            || settings.gateway_name === 'alma_in_page'
        ) {
            event.stopPropagation()
            const {shouldCreateAccount, ...restOfDataTest} = dataTest

            function isDifferentAddress(billing, shipping) {
                for (const key in billing) {
                    if (billing[key] !== shipping[key]) {
                        return true;
                    }
                }
                return false;
            }

            const areShippingAndBillingAddressDifferent = isDifferentAddress(propsData.shippingData.shippingAddress, propsData.billing.billingAddress)
            
            var data = {
                'action': 'alma_do_checkout_in_page',
                'fields': {
                    'shipping_address': propsData.shippingData.shippingAddress,
                    'billing_address': {...propsData.billing.billingAddress},
                    ...restOfDataTest,
                    'ship_to_different_address': areShippingAndBillingAddressDifferent,
                    'createaccount': dataTest.shouldCreateAccount,
                    'alma_fee_plan': globalSelectedFeePlan,
                    [almaCheckoutNonce]: settings.nonce_value,
                    'payment_method': settings.gateway_name,
                },
                [almaCheckoutNonce]: settings.nonce_value,
                'woocommerce-process-checkout-nonce': settings.woocommerce_process_checkout_nonce,
                'payment_method': settings.gateway_name,
                'alma_fee_plan': globalSelectedFeePlan,
                'alma_fee_plan_in_page': globalSelectedFeePlan,
                'is_woo_block': true
            };

            var loading = "<div class='loadingIndicator'><img src='https://cdn.almapay.com/img/animated-logo-a.svg' alt='Loading' /></div>";
            $("body").append("<div class='alma-loader-wrapper'>" + loading + "</div>");


            // Create the payment id and order.
            jQuery.post(ajax_object.ajax_url, data)
                .done(
                    function (response) {
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
                        $('.alma-loader-wrapper').remove();
                        location.reload();
                    }
                );

        }
    };

    function cancel_order(orderId) {
        var data = {
            'action': 'alma_cancel_order_in_page',
            'order_id': orderId
        };

        jQuery.post(ajax_object.ajax_url, data)
    }

})(jQuery);
