/**
 * Gateway Blocks Page.
 *
 * @since 5.3.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/assets
 */

/**
 * Global Alma Settings Object
 *
 * @typedef {object} AlmaSettings - Alma Settings Object
 * @property {boolean} success - True if the settings were successfully retrieved.
 * @property {string} merchant_id - The merchant ID for Alma.
 * @property {string} environment - The environment (e.g., live, test).
 * @property {string} language - The language code (e.g., en, fr).
 * @property {boolean} is_in_page - Indicates if in-page checkout is enabled.
 * @property {object} gateway_settings - The settings for each payment gateway.
 * @property {AlmaGatewaySettings} gateway_settings.alma_paynow_gateway - Settings for Alma Pay Now gateway.
 * @property {AlmaGatewaySettings} gateway_settings.alma_pnx_gateway - Settings for Alma Pnx gateway.
 * @property {AlmaGatewaySettings} gateway_settings.alma_paylater_gateway - Settings for Alma Pay Later gateway.
 * @property {AlmaGatewaySettings} gateway_settings.alma_Credit_gateway - Settings for Alma Credit gateway.
 */

/**
 * Gateway Settings Object
 *
 * @typedef {object} AlmaGatewaySettings - Settings for a specific payment gateway.
 * @property {string} name - The name of the gateway.
 * @property {string} gateway_name - The unique identifier for the gateway.
 * @property {string} title - The title of the gateway.
 * @property {string} description - The description of the gateway.
 * @property {boolean} is_pay_now - Indicates if the gateway is a pay-now option.
 * @property {string} label_button - The label for the payment button.
 * @property {object} fee_plans_settings - The available payment plans for the gateway.
 */

/**
 * Fee Plan Details Object
 *
 * @typedef {object} feePlansSettings The details of a payment plan.
 * @property {string} planKey - The unique key for the payment plan.
 * @property {Array<object>} paymentPlan - The payment plan details.
 * @property {number} customerTotalCostAmount - The total cost amount for the customer.
 * @property {number} installmentsCount - The number of installments for the payment plan.
 * @property {number} deferredDays - The number of deferred days for the payment plan.
 * @property {number} deferredMonths - The number of deferred months for the payment plan.
 * @property {number} annualInterestRate - The annual interest rate for the payment plan.
 */


/**
 * Global AlmaInitSettings variable from Block.
 *
 * @typedef {object} AlmaInitSettings
 * @property {string} checkout_url
 */

// phpcs:ignoreFile
import {storeKey} from "../stores/alma-store";
import './alma-gateway-block.css';
import {createRoot, useEffect, useRef} from "@wordpress/element";
import {select, useSelect} from "@wordpress/data";
import {fetchAlmaSettings} from "./hooks/fetchAlmaSettings";
import {DisplayAlmaBlock} from "./components/DisplayAlmaBlock";
import {DisplayAlmaInPageBlock} from "./components/DisplayAlmaInPageBlock";
import React from "react";
import {Label} from "./components/Label";

(function ($) {


    var inPage = undefined;
    const {CHECKOUT_STORE_KEY, CART_STORE_KEY} = window.wc.wcBlocksData
    const isBlocksCheckout =
        window.wc &&
        window.wc.wcBlocksRegistry &&
        document.querySelector('.wc-block-components-checkout, .wc-block-checkout');

    /**
     * Check if gateway can make payment
     * @param gatewayEligibility
     * @returns {boolean}
     */
    const gatewayCanMakePayment = (gatewayEligibility) => {
        return true;
    };

    /**
     * Set InPage instance
     * @param instance
     */
    const setInPage = (instance) => {
        inPage = instance;
    };

    const CartObserver = () => {
        // Subscribe to the cart total, shipping rates and customer addresses
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

        // Use ref to track if we've already fetched for this cart state
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

        // Register the payment gateway blocks
        useEffect(() => {
            console.log('Registering payment gateway blocks from CartObserver', {
                almaSettings,
                isCalculating,
                isLoading,
                cartTotal
            });
            if (!isCalculating && !isLoading && almaSettings) {
                // resetPaymentGateways(almaSettings);
                registerPaymentGatewayBlock(almaSettings, cartTotal, true);
            }
        }, [almaSettings, isCalculating, isLoading, cartTotal]);

        return null; // This component doesn't render anything
    };

    /**
     * Generate Gateway Block
     * The selectable one in the checkout payment methods list
     *
     * @param gatewaySettings The Gateway Settings
     * @param canMakePayment Indicates if the gateway can make payment
     * @returns {{name: string, label, content: *, edit: *, placeOrderButtonLabel: string, canMakePayment: function(): *, ariaLabel: *}}
     */
    const generateGatewayBlock = (gatewaySettings, canMakePayment) => {

        console.log(`Generating block for ${gatewaySettings.gateway_name}:`, {
            name: gatewaySettings.name,
            gateway_name: gatewaySettings.gateway_name,
            canMakePayment,
            gatewaySettings,
        });

        const BlockContent = generateBlockContent(
            select(storeKey).getAlmaSettings(),
            gatewaySettings
        );

        console.log('Generating gateway block for: ', gatewaySettings.gateway_name, gatewaySettings.title);
        return {
            name: gatewaySettings.name,
            gatewayId: gatewaySettings.gateway_name,
            paymentMethodId: gatewaySettings.name,
            label: (
                <Label
                    title={window.wp.htmlEntities.decodeEntities(gatewaySettings.title)}
                />
            ),
            content: <BlockContent/>,
            edit: <BlockContent/>,
            canMakePayment: () => {
                return true;//canMakePayment;
            },
            ariaLabel: gatewaySettings.title,
            placeOrderButtonLabel: gatewaySettings.label_button,
            supports: {
                features: gatewaySettings?.supports ?? ['products']
            },
        }
    }

    /**
     * Get Content Block
     *
     * @param almaSettings The Alma Settings
     * @param gatewaySettings The Gateway Settings
     * @returns {JSX.Element} The Content Block
     */
    const generateBlockContent = (almaSettings, gatewaySettings) => {
        return (wc_props) => {
            const {eventRegistration, emitResponse} = wc_props;
            const {onPaymentProcessing} = eventRegistration;

            useEffect(() => {
                const unsubscribe = onPaymentProcessing(async () => {
                    if (almaSettings.is_in_page) {
                        return handleInPagePayment(gatewaySettings, emitResponse);
                    } else {
                        return handleStandardPayment(gatewaySettings, emitResponse);
                    }
                });
                return () => unsubscribe();
            }, [onPaymentProcessing]);

            return almaSettings.is_in_page ? (
                <DisplayAlmaInPageBlock
                    eventRegistration={eventRegistration}
                    emitResponse={emitResponse}
                    isPayNow={gatewaySettings.is_pay_now}
                    storeKey={storeKey}
                    almaSettings={almaSettings}
                    gatewaySettings={gatewaySettings}
                    setInPage={setInPage}
                />
            ) : (
                <DisplayAlmaBlock
                    eventRegistration={eventRegistration}
                    emitResponse={emitResponse}
                    almaSettings={almaSettings}
                    gatewaySettings={gatewaySettings}
                    storeKey={storeKey}
                />
            );
        };
    };

    /**
     * Handle Classic Payment
     *
     * @param gatewaySettings
     * @param emitResponse
     * @returns {Promise<{type: *, meta: {paymentMethodData: {alma_plan_key: string, payment_method: string}}}>}
     */
    const handleStandardPayment = (gatewaySettings, emitResponse) => {

        console.log('Redirect to Alma Checkout');

        const selectedPlan = select(storeKey).getSelectedFeePlan();
        console.log('Selected plan:', selectedPlan);

        const paymentMethodData = {
            alma_fee_plan: String(selectedPlan || ''),
            payment_method: String(gatewaySettings.gateway_name || ''),
        };

        return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
                paymentMethodData: paymentMethodData
            }
        };
    };

    /**
     * Handle In-Page Payment
     *
     * @param gatewaySettings
     * @param emitResponse
     * @returns {Promise<{type: *, meta: {paymentMethodData: {alma_plan_key: string, payment_method: string}}}>}
     */
    const handleInPagePayment = async (gatewaySettings, emitResponse) => {

        console.log('In Page Checkout');
        const selectedPlan = select(storeKey).getSelectedFeePlan();
        console.log('Selected plan:', selectedPlan);

        const paymentMethodData = {
            alma_plan_key: String(selectedPlan || ''),
            payment_method: String(gatewaySettings.gateway_name || ''),
        };

        return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
                paymentMethodData: paymentMethodData
            }
        };
    };

    const resetPaymentGateways = (almaSettings) => {
        for (const gatewayName in almaSettings.gateway_settings) {
            console.log('Resetting gateway:', gatewayName);
            const gatewaySettings = almaSettings.gateway_settings[gatewayName]
            const Block_Gateway_Alma = generateGatewayBlock(gatewaySettings, <></>, false)
            window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Alma);
        }
    }

    /**
     * Register Available Payment Gateway Blocks
     *
     * @param almaSettings The Alma Settings
     * @param cartTotal
     * @param init
     */
    const registerPaymentGatewayBlock = (almaSettings, cartTotal, init = false) => {

        if (!almaSettings.gateway_settings || typeof almaSettings.gateway_settings !== 'object' || Object.keys(almaSettings.gateway_settings).length === 0) {
            console.log('empty gateway_settings, skipping');
            return;
        }
        console.log('Registering payment gateway blocks with eligibility:', almaSettings.gateway_settings);

        for (const gatewayName in almaSettings.gateway_settings) {

            const gatewaySettings = almaSettings.gateway_settings[gatewayName]

            if (!gatewaySettings || typeof gatewaySettings !== 'object' || Object.keys(gatewaySettings).length === 0) {
                console.log('empty gateway, skipping', gatewayName);
                continue;
            }
            console.log('Processing gateway:', gatewayName);

            if (!almaSettings) {
                // If settings are not available, unregister the gateway
                try {
                    // window.wc.wcBlocksRegistry.unregisterPaymentMethod(gateway);
                } catch (e) {
                    // Gateway might not be registered yet, ignore error
                    console.log(e)
                }
                continue;
            }

            const AlmaGatewayBlock = generateGatewayBlock(
                gatewaySettings,
                init ? true : gatewayCanMakePayment(gatewaySettings)
            );

            console.log('AlmaGatewayBlock', AlmaGatewayBlock)

            try {
                window.wc.wcBlocksRegistry.registerPaymentMethod(AlmaGatewayBlock);
            } catch (e) {
                console.warn('Failed to register payment method:', gatewayName, e);
            }
        }

        setTimeout(() => {
            // Déclenche une re-validation des méthodes de paiement
            const event = new CustomEvent('wc-blocks-registry-updated');
            document.dispatchEvent(event);
        }, 100);

        debugBefore();
        debugAfter();

    };

    const debugBefore = () => {
        // Check if WooCommerce Blocks registry exists
        if (window.wc && window.wc.wcBlocksRegistry) {

            // Get all registered payment methods (Block-based)
            const paymentMethods = window.wc.wcBlocksRegistry.getPaymentMethods?.();

            if (paymentMethods) {
                console.log("=== WooCommerce Block Payment Methods ===", paymentMethods);
                Object.entries(paymentMethods).forEach(([key, method]) => {
                    console.log(`ID: ${key}`);
                    console.log(`Label: ${method.label || 'N/A'}`);
                    console.log(`Content: ${method.content || 'N/A'}`);
                    console.log(`Edit: ${method.edit || 'N/A'}`);
                    console.log(`placeOrderButtonLabel: ${method.placeOrderButtonLabel || 'N/A'}`);
                    // console.log(`Icon: ${method.icon || 'N/A'}`);
                    console.log(`Can make Payment: ${method.canMakePayment() || 'N/A'}`);
                    console.log('------------------------');
                });
            } else {
                console.log("No block-based payment methods found. Make sure you're on the checkout page and blocks are initialized.");
            }

        } else {
            console.log("WooCommerce Blocks registry not found on this page.");
        }
    }

    const debugAfter = () => {
        // Check if WooCommerce Blocks registry exists
        if (window.wc && window.wc.wcBlocksRegistry) {

            // Get all registered payment methods (Block-based)
            const {paymentStore} = window.wc.wcBlocksData;
            const paymentMethods = wp.data.select(paymentStore).getAvailablePaymentMethods();

            if (paymentMethods) {
                console.log("=== WooCommerce Block Payment Methods ===", paymentMethods);
                Object.entries(paymentMethods).forEach(([key, method]) => {
                    console.log(`ID: ${key}`);
                    console.log('------------------------');
                });
            } else {
                console.log("No block-based payment methods found. Make sure you're on the checkout page and blocks are initialized.");
            }

        } else {
            console.log("WooCommerce Blocks registry not found on this page.");
        }
    }

    /**
     * Init the Alma Gateway Block and the Cart Observer by mounting the React Component
     */
    const initAlmaGatewayBlock = () => {
        if (isBlocksCheckout && window.wc.wcSettings) {
            console.log("Mount React Component for Alma Cart Observer");
            const rootDiv = document.createElement('div');
            rootDiv.id = 'alma-cart-observer-root';
            document.body.appendChild(rootDiv);

            if (window.React && window.ReactDOM) {
                const root = createRoot(rootDiv);
                root.render(<CartObserver/>);
            } else {
                console.error('React or ReactDOM not found on the page.');
            }
        } else {
            console.error('WooCommerce dependencies not loaded');
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        initAlmaGatewayBlock();
    });


    // Remplacez temporairement votre logique complexe par :
    function simple_register_test() {

        if (isBlocksCheckout && window.wc.wcSettings) {
            const simpleBlock = {
                name: 'toto',
                label: React.createElement('div', {}, 'Test Alma Manual'),
                content: React.createElement('div', {}, 'Test content'),
                edit: React.createElement('div', {}, 'Test content'),
                canMakePayment: () => true,
                ariaLabel: 'Test Alma Payment',
                supports: {features: ['products']}
            };
            console.log('Registering simple test block:', simpleBlock);
            window.wc.wcBlocksRegistry.registerPaymentMethod(simpleBlock);
            debug();
        } else {
            console.error('WooCommerce dependencies not loaded for simple test block');
        }
    }

// Appelez cette fonction après le chargement
    $(document).ready(() => {
        //setTimeout(simple_register_test, 5000);
    });


})(jQuery);
