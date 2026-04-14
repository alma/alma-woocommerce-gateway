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

/**
 * Global AlmaSettings variable from WooCommerce Block.
 *
 * @typedef {object} AlmaSettings
 * @property {boolean} success - Indicates if the data retrieval was successful.
 * @property {boolean} is_in_page - True if checkout is embedded within the page.
 * @property {string} nonce_value - Nonce input HTML used for secured AJAX calls.
 * @property {object} gateway_settings - Contains all Alma payment gateways configuration.
 *
 * @typedef {object} GatewaySettings
 * @property {AlmaGateway} alma_pnx_gateway - Pay in installments gateway.
 * @property {AlmaGateway} alma_credit_gateway - Credit with Alma gateway.
 * @property {AlmaGateway} alma_paylater_gateway - Pay later with Alma gateway.
 * @property {AlmaGateway} alma_paynow_gateway - Pay now with Alma gateway.
 *
 * @typedef {object} AlmaGateway
 * @property {string} name - Internal block name.
 * @property {string} gateway_name - WooCommerce gateway identifier.
 * @property {string} title - Gateway display title.
 * @property {string} [description] - Gateway description (optional).
 * @property {boolean} [is_pay_later] - Indicates if it is a paylater type gateway.
 * @property {boolean} [is_pay_now] - Indicates if it is a paynow type gateway.
 * @property {string} [label_button] - Button label used in UI.
 * @property {FeePlansSettings} [fee_plans_settings] - Available payment plans (credit/installments gateways only).
 *
 * @typedef {object} FeePlansSettings
 * @property {FeePlan} general_1_0_0 - Pay now plan configuration.
 * @property {FeePlan} general_2_0_0 - Pnx 2-installment plan configuration.
 * @property {FeePlan} general_3_0_0 - Pnx3-installment plan configuration.
 * @property {FeePlan} general_4_0_0 - Pnx4-installment plan configuration.
 * @property {FeePlan} general_6_0_0 - Credit 6-installment plan configuration.
 * @property {FeePlan} general_10_0_0 - Credit 10-installment plan configuration.
 * @property {FeePlan} general_12_0_0 - Credit 12-installment plan configuration.
 * @property {FeePlan} general_24_0_0 - Credit 24-installment plan configuration.
 * @property {FeePlan} general_1_30_0 - Pay later 30-days plan configuration.
 *
 * @typedef {object} FeePlan
 * @property {string} planKey - Unique key identifying the plan.
 * @property {number} installmentsCount - Number of installments.
 * @property {number} annualInterestRate - Annual interest rate applied to the plan.
 * @property {number} customerTotalCostAmount - Total cost paid by the customer (including fees and interests).
 * @property {number} deferredDays - Deferred payment days before first installment.
 * @property {number} deferredMonths - Deferred payment months before first installment.
 * @property {PaymentSchedule[]} paymentPlan - Detailed schedule of installments.
 *
 * @typedef {object} PaymentSchedule
 * @property {number} due_date - UNIX timestamp of the due date.
 * @property {string} localized_due_date - Localized readable due date (e.g., "28 mai 2026").
 * @property {number} purchase_amount - Amount of the principal payment.
 * @property {number} customer_fee - Additional customer fees.
 * @property {number} customer_interest - Interest amount.
 * @property {number} total_amount - Total amount to pay for this installment.
 */

// phpcs:ignoreFile
import {storeKey} from "../stores/alma-store";
import {createRoot, useEffect, useRef, useState} from '@wordpress/element';
import {dispatch, select, useSelect} from '@wordpress/data';
import {Label} from "./components/Label";
import './alma-gateway-block.css';
import {DisplayAlmaInPageBlock} from "./components/DisplayAlmaInPageBlock";
import {DisplayAlmaBlock} from "./components/DisplayAlmaBlock";
import {fetchAlmaSettings} from "./hooks/almaSettings";

// Hydrate the store synchronously from server-rendered eligibility so payment
// methods register on the first React render (WC 9.7 finalizes payment methods
// before the async fetch of /alma_checkout_data can complete).
if (window.AlmaInitSettings?.gateway_settings) {
    dispatch(storeKey).setAlmaSettings(window.AlmaInitSettings);
}

(function ($) {

    /** Get Cart and Checkout store keys */
    const {CART_STORE_KEY, CHECKOUT_STORE_KEY} = window.wc.wcBlocksData

    /**
     * Check if the Gateway can make payment
     *
     * @param gatewaySettings
     * @returns {boolean}
     */
    const gatewayCanMakePayment = (gatewaySettings) => {
        let canMakePayment = true
        if (!gatewaySettings?.fee_plans_settings || Object.keys(gatewaySettings.fee_plans_settings).length === 0) {
            canMakePayment = false;
        }
        return canMakePayment
    }

    /**
     * Cart Observer Component
     *
     * @constructor
     */
    const CartObserver = () => {
        // Subscribe to the cart total
        const {cartTotal, shippingRates, currencyMinorUnit} = useSelect((select) => ({
            cartTotal: select(CART_STORE_KEY).getCartTotals().total_price, // Total price is in minor unit depending on the currency_minor_unit
            currencyMinorUnit: select(CART_STORE_KEY).getCartTotals().currency_minor_unit,
            shippingRates: select(CART_STORE_KEY).getShippingRates()
        }), []);
        // Subscribe to the eligibility
        const {almaSettings, allGatewaysSettings, isLoading} = useSelect(
            (select) => ({
                almaSettings: select(storeKey).getAlmaSettings(),
                allGatewaysSettings: select(storeKey).getAllGatewaysSettings(),
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

        const [inPageInstance, setInPageInstance] = useState(undefined)

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
            const totalInCent = parseInt(cartTotal) * Math.pow(10, 2 - currencyMinorUnit)
            registerPaymentGateway(almaSettings, allGatewaysSettings, storeKey, totalInCent, inPageInstance, setInPageInstance)
        }
    };

    /**
     * Register All Payment Gateway Blocks
     *
     * @param almaSettings All AlmaSettings
     * @param allGatewaysSettings
     * @param storeKey
     * @param inPageInstance
     * @param setInPageInstance
     * @param init
     * @param cartTotal
     */
    const registerPaymentGateway = (almaSettings, allGatewaysSettings, storeKey, cartTotal, inPageInstance, setInPageInstance, init = false) => {

        for (const gatewayName in allGatewaysSettings) {

            const gatewaySettings = allGatewaysSettings?.[gatewayName] ?? {};

            // If gateway Block is available, we register it
            if (gatewaySettings) {
                const blockContent = getContentBlock(almaSettings, gatewayName, storeKey, cartTotal, inPageInstance, setInPageInstance)
                const AlmaGatewayBlock = generateGatewayBlock(gatewaySettings, blockContent, init ? true : gatewayCanMakePayment(gatewaySettings));
                if (gatewayCanMakePayment(gatewaySettings)) {
                    window.wc.wcBlocksRegistry.registerPaymentMethod(AlmaGatewayBlock);
                }
                console.log('register: ' + gatewayName);
            }
        }
    }

    /**
     * Generate Gateway Block
     *
     * @param gatewaySettings
     * @param blockContent
     * @param canMakePayment
     * @returns {{name: *, label, content: *, edit: *, placeOrderButtonLabel: *, canMakePayment: function(): *, ariaLabel: *}}
     */
    const generateGatewayBlock = (gatewaySettings, blockContent, canMakePayment) => {
        console.log("Generating Gateway block " + blockContent);
        return {
            name: gatewaySettings.gateway_name,
            label: (
                <Label
                    title={window.wp.htmlEntities.decodeEntities(gatewaySettings.title)}
                />
            ),
            content: blockContent,
            edit: blockContent,
            placeOrderButtonLabel: gatewaySettings.label_button,
            canMakePayment: () => canMakePayment,
            ariaLabel: gatewaySettings.title,
        }
    }

    /**
     * Get Content Block
     *
     * @param almaSettings
     * @param gateway
     * @param storeKey
     * @param cartTotal
     * @param inPageInstance
     * @param setInPageInstance
     * @returns {JSX.Element}
     */
    const getContentBlock = (almaSettings, gateway, storeKey, cartTotal, inPageInstance, setInPageInstance) => {

        return almaSettings.is_in_page ? (
            <DisplayAlmaInPageBlock
                gateway={gateway}
                storeKey={storeKey}
                setInPage={setInPageInstance}
                inPage={inPageInstance}
                cartTotal={cartTotal}
            />
        ) : (
            <DisplayAlmaBlock
                gateway={gateway}
                storeKey={storeKey}
                cartTotal={cartTotal}
            />
        )
    }

    /**
     * Mount React Component for Cart Observer
     */
    const mountReactComponent = () => {
        const rootDiv = document.createElement('div');
        document.body.appendChild(rootDiv);

        if (createRoot) {
            const root = createRoot(rootDiv);
            root.render(<CartObserver/>);
        } else {
            // Fallback pour React 17
            ReactDOM.render(<CartObserver/>, rootDiv);
        }
    };

    /**
     * Synchronous initial registration from server-rendered eligibility.
     * WC 9.7 reads the payment method registry before DOMContentLoaded,
     * so we must register before React mounts CartObserver.
     * Wrapped in try/catch to protect the DOMContentLoaded listener below.
     */
    try {
        const initGwSettings = select(storeKey).getAllGatewaysSettings();
        const initAlmaSettings = select(storeKey).getAlmaSettings();
        if (Object.keys(initGwSettings).length > 0) {
            const cartTotals = select(CART_STORE_KEY).getCartTotals() || {};
            const cartTotal = parseInt(cartTotals.total_price || 0) * Math.pow(10, 2 - (cartTotals.currency_minor_unit || 0));

            for (const gatewayName in initGwSettings) {
                const gw = initGwSettings[gatewayName];
                if (gw?.gateway_name) {
                    const blockContent = getContentBlock(initAlmaSettings, gatewayName, storeKey, cartTotal, undefined, () => {});
                    // canMakePayment reads from the store dynamically so it reflects
                    // the async eligibility fetch once it completes.
                    const dynamicCanMakePayment = () => {
                        const current = select(storeKey).getGatewaySettings(gatewayName);
                        if (!current || !('fee_plans_settings' in current)) return true;
                        return Object.keys(current.fee_plans_settings).length > 0;
                    };
                    const block = generateGatewayBlock(gw, blockContent, true);
                    block.canMakePayment = dynamicCanMakePayment;
                    window.wc.wcBlocksRegistry.registerPaymentMethod(block);
                }
            }
        }
    } catch (e) {
        console.error('Alma: synchronous gateway registration failed', e);
    }

    /**
     * Init Alma Gateway Blocks on DOMContentLoaded
     */
    document.addEventListener('DOMContentLoaded', function () {
        mountReactComponent()
    });
})(jQuery);
