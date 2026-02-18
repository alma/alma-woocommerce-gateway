/**
 * Example of Alma settings structure stored in the Redux store:
 * {
 *   success: true,
 *   is_in_page: false,
 *   nonce_value: "<input ... />",
 *   gateway_settings: {
 *     alma_pnx_gateway: {
 *       name: "alma_pnx_gateway_block",
 *       gateway_name: "alma_pnx_gateway",
 *       title: "Pay in installments with Alma",
 *       ...
 *     },
 *     alma_credit_gateway: {
 *       name: "alma_credit_gateway_block",
 *       gateway_name: "alma_credit_gateway",
 *       title: "Credit with Alma",
 *       description: "",
 *       is_pay_now: false,
 *       label_button: "Pay With Alma",
 *       fee_plans_settings: {
 *         general_10_0_0: {
 *           planKey: "general_10_0_0",
 *           installmentsCount: 10,
 *           annualInterestRate: 0,
 *           customerTotalCostAmount: 0,
 *           deferredDays: 0,
 *           deferredMonths: 0,
 *           paymentPlan: [
 *             {
 *               due_date,
 *               localized_due_date,
 *               purchase_amount,
 *               customer_fee,
 *               customer_interest,
 *               total_amount
 *             },
 *             ... (10 échéances)
 *           ]
 *         },
 *         general_12_0_0: {
 *           planKey: "general_12_0_0",
 *           installmentsCount: 12,
 *           annualInterestRate: 0,
 *           customerTotalCostAmount: 0,
 *           deferredDays: 0,
 *           deferredMonths: 0,
 *           paymentPlan: [
 *             {
 *               due_date,
 *               localized_due_date,
 *               purchase_amount,
 *               customer_fee,
 *               customer_interest,
 *               total_amount
 *             },
 *             ... (12 échéances)
 *           ]
 *         }
 *       }
 *     },
 *     alma_paylater_gateway: {
 *       name: "alma_paylater_gateway_block",
 *       gateway_name: "alma_paylater_gateway",
 *       title: "Pay later with Alma",
 *       ...
 *     },
 *     alma_paynow_gateway: {
 *       name: "alma_paynow_gateway_block",
 *       gateway_name: "alma_paynow_gateway",
 *       title: "Pay now with Alma",
 *       ...
 *     }
 *   }
 * }
 */

import {createReduxStore, register} from "@wordpress/data";

/**
 * Store key for the Alma store.
 * @type {string}
 */
export const storeKey = 'alma/alma-store';
const DEFAULT_STATE = {
    almaSettings: {},
    allGatewaysSettings: {},
    selectedFeePlan: null,
    isLoading: false,
};

const actions = {
    /**
     * Set Alma settings in the store.
     * @param data
     * @returns {{type: string, payload: *}}
     */
    setAlmaSettings(data) {
        console.log('set settings in store:', data);
        // Separate almaSettings and allGatewaysSettings
        const {gateway_settings, ...almaSettings} = data || {};
        return {
            type: 'SET_ALMA_SETTINGS',
            payload: {
                almaSettings,
                allGatewaysSettings: gateway_settings || {},
            },
        };
    },
    setSelectedFeePlan(plan) {
        return {
            type: 'SET_SELECTED_FEE_PLAN',
            payload: plan,
        };
    },
    setLoading(isLoading) {
        return {
            type: 'SET_LOADING',
            payload: isLoading,
        };
    },
};

function reducer(state = DEFAULT_STATE, action) {
    switch (action.type) {
        case 'SET_ALMA_SETTINGS':
            return {
                ...state,
                almaSettings: action.payload.almaSettings,
                allGatewaysSettings: action.payload.allGatewaysSettings,
            };
        case 'SET_SELECTED_FEE_PLAN':
            return {
                ...state,
                selectedFeePlan: action.payload,
            };
        case 'SET_LOADING':
            return {
                ...state,
                isLoading: action.payload,
            };
        default:
            console.warn(`Unhandled action type: ${action.type}`);
            return state;
    }
}

const selectors = {
    /**
     * Get Alma settings from the store.
     * @param state
     * @returns {*|{}}
     */
    getAlmaSettings(state) {
        return state.almaSettings || {};
    },
    /**
     * Get all gateways settings or a specific gateway's settings if a gateway name is provided.
     * @param state
     * @returns {object}
     */
    getAllGatewaysSettings(state) {
        return state.allGatewaysSettings || {};
    },
    /**
     * Get settings for a specific gateway.
     * @param state
     * @param gateway
     * @returns {*|{}}
     */
    getGatewaySettings(state, gateway) {
        return state.allGatewaysSettings?.[gateway] || {};
    },
    /**
     * Get the selected fee plan from the store.
     * @param state
     * @returns {string|*|null}
     */
    getSelectedFeePlan(state) {
        return state.selectedFeePlan ?? null;
    },
    /**
     * Check if the store is in a loading state.
     * @param state
     * @returns {boolean}
     */
    isLoading(state) {
        return state.isLoading ?? false;
    }
};

const almaReduxStore = createReduxStore('alma/alma-store', {
    reducer,
    actions,
    selectors,
});
register(almaReduxStore);