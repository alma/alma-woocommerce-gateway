/**
 * Gateway Blocks Page.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

/**
 * Global BlocksData variable from Block.
 *
 * @typedef {object} BlocksData
 * @property {string} url - Webhook URL
 * @property {object} init_eligibility - Initial eligibility data
 * @property {number} cart_total - Initial cart total
 * @property {string} nonce_value - Token value for AJAX calls
 * @property {boolean} is_in_page - Is In Page mode enabled?
 * @property {string} merchant_id - Merchant ID (optional, mandatory if in page is enabled)
 * @property {string} environment - Environment (optional, mandatory if in page is enabled)
 * @property {string} language - Language (optional, mandatory if in page is enabled)
 * @property {string} ajax_url - AJAX URL (optional, mandatory if in page is enabled)
 */

// phpcs:ignoreFile
import {storeKey} from "../stores/alma-store";
import {useEffect} from '@wordpress/element';
import {select, useSelect} from '@wordpress/data';
import {Label} from "./components/Label";
import './alma-gateway-block.css';
import {DisplayAlmaInPageBlock} from "./components/DisplayAlmaInPageBlock";
import {DisplayAlmaBlock} from "./components/DisplayAlmaBlock";
import {fetchAlmaSettings} from "./hooks/fetchAlmaSettings";

(function ($) {
    let inPage = undefined;
    const {CART_STORE_KEY, CHECKOUT_STORE_KEY} = window.wc.wcBlocksData
    const CartObserver = () => {
        // Subscribe to the cart total
        const {cartTotal, shippingRates} = useSelect((select) => ({
            cartTotal: select(CART_STORE_KEY).getCartTotals().total_price,
            shippingRates: select(CART_STORE_KEY).getShippingRates()
        }), []);
        // Subscribe to the eligibility
        const {almaSettings, isLoading} = useSelect(
            (select) => ({
                almaSettings: select(storeKey).getAlmaSettings(),
                isLoading: select(storeKey).isLoading(),
            }), []
        );
        const {isCalculating} = useSelect((select) => ({
            isCalculating: select(CHECKOUT_STORE_KEY).isCalculating(),
        }), []);

        // Use the cart total to fetch the new eligibility
        useEffect(() => {
            // BlockData is a global variable defined in the PHP file with the wp_localize_script function
            fetchAlmaSettings(storeKey, BlocksData.checkout_url)
        }, [cartTotal, shippingRates]);


        // Register the payment gateway block
        if (!isCalculating && !isLoading) {
            // For each gateway in eligibility result, we register a block
            // before registering the payment gateway, we reset the payment gateways to force gutenberg reload
            // resetPaymentGateways(almaSettings)
            registerPaymentGateway(almaSettings.gateway_settings, cartTotal)
        }
    };

    const resetPaymentGateways = (almaSettings) => {
        for (const gatewayName in almaSettings.gateway_settings) {
            console.log('Resetting gateway:', gatewayName);
            const gatewaySettings = almaSettings.gateway_settings[gatewayName]
            const settings = window.wc.wcSettings.getSetting(`${gateway}_block_data`, null)
            const Block_Gateway_Alma = generateGatewayBlock(gatewaySettings, <></>, false)
            window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);
        }
    }

    const registerPaymentGateway = (gateway_settings, cartTotal, init = false) => {

        for (const gateway in gateway_settings) {

            const gatewaySetting = gateway_settings[gateway]

            console.log('gateway_setting', gatewaySetting)

            const settings = window.wc.wcSettings.getSetting(`${gatewaySetting.gateway_name}_block_data`, null)

            console.log('settings ++++++++++++ ', gatewaySetting, settings)

            // If gateway Block is available, we register it
            if (settings) {
                const blockContent = getContentBlock(BlocksData.is_in_page, settings, cartTotal, gateway)
                const Block_Gateway_Alma = generateGatewayBlock(settings, blockContent, init ? true : true)
                window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);
                console.log('register: ' + gateway);
            }
        }
    }

    const generateGatewayBlock = (settings, blockContent, canMakePayment) => {

        console.log("Generating Gateway block " + blockContent);

        return {
            name: settings.gateway_name,
            label: (
                <Label
                    title={window.wp.htmlEntities.decodeEntities(settings.title)}
                />
            ),
            content: blockContent,
            edit: blockContent,
            placeOrderButtonLabel: settings.label_button,
            canMakePayment: () => canMakePayment,
            ariaLabel: settings.title,
        }
    }
    const getContentBlock = (is_in_page, settings, cartTotal, gateway) => {
        const setInPage = (inPageInstance) => {
            inPage = inPageInstance
        }
        const isPayNow = (settings.gateway_name === "alma_pay_now" || settings.gateway_name === "alma_in_page_pay_now");

        return is_in_page ? (
            <DisplayAlmaInPageBlock
                isPayNow={isPayNow}
                store_key={storeKey}
                settings={settings}
                gateway={gateway}
                setInPage={setInPage}
            />
        ) : (
            <DisplayAlmaBlock
                isPayNow={isPayNow}
                store_key={storeKey}
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

    $(window).bind("load", function () {
        addActionToPaymentButton();
    });


    const addActionToPaymentButton = () => {
        document.querySelector(".wc-block-components-checkout-place-order-button").addEventListener(
            "click",
            addActionToPaymentButtonListener
        );
    }

    const addActionToPaymentButtonListener = (event) => {
        const {CHECKOUT_STORE_KEY, CART_STORE_KEY} = window.wc.wcBlocksData
        const store = select(CHECKOUT_STORE_KEY);
        const cartStore = select(CART_STORE_KEY);
        const almaStore = select(storeKey);
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

        if (
            settings.gateway_name === 'alma_paynow_gateway'
            || settings.gateway_name === 'alma_paylater_gateway'
            || settings.gateway_name === 'alma_pnx_gateway'
            || settings.gateway_name === 'alma_credit_gateway'
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
                    [almaCheckoutNonce]: BlocksData.nonce_value,
                    'payment_method': settings.gateway_name,
                },
                [almaCheckoutNonce]: BlocksData.nonce_value,
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

    function init_gateway_block() {

        console.log("Init Gateway Block", BlocksData);

        fetchAlmaSettings(storeKey, BlocksData.checkout_url).then(
            () => {
                const {almaSettings} = useSelect(
                    (select) => ({
                        almaSettings: select(storeKey).getAlmaSettings(),
                    }), []
                );
                const eligibility = almaSettings.gateway_settings;

                registerPaymentGateway(gateway_settings, 0, true)
            }
        )
    }

    init_gateway_block()
})(jQuery);
