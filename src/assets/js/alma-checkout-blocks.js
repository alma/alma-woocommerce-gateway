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
import {select, useSelect} from '@wordpress/data';
import {fetchAlmaEligibility} from "./hooks/fetchAlmaEligibility";
import {Label} from "./components/Label";
import {DisplayAlmaBlocks} from "./components/DisplayAlmaBlocks";
import {DisplayAlmaInPageBlocks} from "./components/DisplayAlmaInPageBlocks";
import '../css/alma-checkout-blocks.css';

(function ($) {
    const store_key = 'alma/alma-store'
    var inPage = undefined;
    const {CART_STORE_KEY} = window.wc.wcBlocksData

    const CartObserver = () => {
        // Subscribe to the cart total
        const {cartTotal} = useSelect((select) => ({
            cartTotal: select(CART_STORE_KEY).getCartTotals()
        }), []);

        // Subscribe to the eligibility
        const {eligibility} = useSelect(
            (select) => ({
                eligibility: select(store_key).getAlmaEligibility()
            }), []
        );

        // Use the cart total to fetch the new eligibility
        useEffect(() => {
            // BlockData is a global variable defined in the PHP file with the wp_localize_script function
            fetchAlmaEligibility(store_key, BlocksData.url)
        }, [cartTotal]);

        // Register the payment gateway blocks
        useEffect(() => {
            // For each gateway in eligibility result, we register a block
            for (const gateway in eligibility) {
                const settings = window.wc.wcSettings.getSetting(`${gateway}_data`, null)
                const is_in_page = settings.is_in_page
                const blockContent = getContentBlock(is_in_page, settings, cartTotal, gateway)

                const Block_Gateway_Alma = {
                    name: settings.gateway_name,
                    label: (
                        <Label
                            title={window.wp.htmlEntities.decodeEntities(settings.title)}
                        />
                    ),
                    content: blockContent,
                    edit: blockContent,
                    placeOrderButtonLabel: settings.label_button,
                    canMakePayment: () => {
                        return gatewayCanMakePayment(eligibility[gateway])
                    },
                    ariaLabel: settings.title,
                }

                window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);
            }
        }, [eligibility]);
        return null
    };

    const getContentBlock = (is_in_page, settings, cartTotal, gateway) => {
        const setInPage = (inPageInstance) => {
            inPage = inPageInstance
        }
        const isPayNow = (settings.gateway_name === "alma_pay_now" || settings.gateway_name === "alma_in_page_pay_now");

        return is_in_page ? (
            <DisplayAlmaInPageBlocks
                isPayNow={isPayNow}
                store_key={store_key}
                settings={settings}
                gateway={gateway}
                setInPage={setInPage}
            />
        ) : (
            <DisplayAlmaBlocks
                isPayNow={isPayNow}
                store_key={store_key}
                settings={settings}
                gateway={gateway}
            />
        )
    }
    const gatewayCanMakePayment = (gateway_eligibility) => {
        let canMakePayment = true
        if (Object.keys(gateway_eligibility).length === 0) {
            canMakePayment = false
        }
        return canMakePayment
    }

    const mountReactComponent = () => {
        const rootDiv = document.createElement('div');
        document.body.appendChild(rootDiv);

        ReactDOM.render(<CartObserver/>, rootDiv);
    };

    $(document).ready(() => {
        mountReactComponent();
    });

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
        const {CHECKOUT_STORE_KEY, CART_STORE_KEY} = window.wc.wcBlocksData
        const store = select(CHECKOUT_STORE_KEY);
        const cartStore = select(CART_STORE_KEY);
        const almaStore = select(store_key);
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
            || settings.gateway_name === 'alma_in_page_pnx_plus_4'
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

            const areShippingAndBillingAddressDifferent = isDifferentAddress(cartStore.getCustomerData().shippingAddress, cartStore.getCustomerData().billingAddress)

            var data = {
                'action': 'alma_do_checkout_in_page',
                'fields': {
                    'shipping_address': cartStore.getCustomerData().shippingAddress,
                    'billing_address': {...cartStore.getCustomerData().billingAddress},
                    ...restOfDataTest,
                    'ship_to_different_address': areShippingAndBillingAddressDifferent,
                    'createaccount': dataTest.shouldCreateAccount,
                    'alma_fee_plan': almaStore.getSelectedFeePlan(),
                    [almaCheckoutNonce]: settings.nonce_value,
                    'payment_method': settings.gateway_name,
                },
                [almaCheckoutNonce]: settings.nonce_value,
                'woocommerce-process-checkout-nonce': settings.woocommerce_process_checkout_nonce,
                'payment_method': settings.gateway_name,
                'alma_fee_plan': almaStore.getSelectedFeePlan(),
                'alma_fee_plan_in_page': almaStore.getSelectedFeePlan(),
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
