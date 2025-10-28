/**
 * Gateway Blocks Page.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

/**
 * Global AlmaInitSettings variable from Block.
 *
 * @typedef {object} AlmaInitSettings
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
import {createRoot, useEffect} from '@wordpress/element';
import {useSelect} from '@wordpress/data';
import {Label} from "./components/Label";
import './alma-gateway-block.css';
import {DisplayAlmaInPageBlock} from "./components/DisplayAlmaInPageBlock";
import {DisplayAlmaBlock} from "./components/DisplayAlmaBlock";
import {fetchAlmaSettings} from "./hooks/fetchAlmaSettings";
import {useRef} from "react";

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

        const previousCartState = useRef({
            cartTotal: null,
            shippingRates: null,
        });
        const isFetching = useRef(false);

        // Use the cart total and addresses to fetch the new eligibility
        useEffect(() => {
            console.log('CartObserver useEffect triggered with cartTotal:', cartTotal, 'shippingRates:', shippingRates);
            // Check if cart state has actually changed
            const cartStateChanged =
                previousCartState.current.cartTotal !== cartTotal ||
                JSON.stringify(previousCartState.current.shippingRates) !== JSON.stringify(shippingRates);

            if (cartStateChanged && !isFetching.current && !isLoading) {
                isFetching.current = true;
                previousCartState.current = {
                    cartTotal,
                    shippingRates
                };

                fetchAlmaSettings(storeKey, AlmaInitSettings.checkout_url).finally(() => {
                    isFetching.current = false;
                });
            }
        }, [cartTotal, shippingRates, isLoading]);


        // Register the payment gateway block
        if (!isCalculating && !isLoading) {
            // For each gateway in eligibility result, we register a block
            // before registering the payment gateway, we reset the payment gateways to force gutenberg reload
            // resetPaymentGateways(almaSettings)
            registerPaymentGateway(almaSettings.gateway_settings)
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

    /**
     * Register All Payment Gateway Blocks
     * @param gateway_settings The gateway settings (one row for each gateway)
     * @param init
     */
    const registerPaymentGateway = (gateway_settings, init = false) => {

        for (const gateway in gateway_settings) {

            const gatewaySetting = gateway_settings[gateway]
            const settings = window.wc.wcSettings.getSetting(`${gatewaySetting.gateway_name}_block_data`, null)

            // If gateway Block is available, we register it
            if (settings) {
                const blockContent = getContentBlock(AlmaInitSettings.is_in_page, settings, gateway)
                const AlmaGatewayBlock = generateGatewayBlock(settings, blockContent, init ? true : true)
                window.wc.wcBlocksRegistry.registerPaymentMethod(AlmaGatewayBlock);
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
    const getContentBlock = (is_in_page, settings, gateway) => {
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

        const root = createRoot(rootDiv);
        root.render(<CartObserver/>);
    };

    document.addEventListener('DOMContentLoaded', function () {
        mountReactComponent()
    });
})(jQuery);
