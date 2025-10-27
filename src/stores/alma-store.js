import {registerStore} from '@wordpress/data';


export const storeKey = 'alma/alma-store';
const DEFAULT_STATE = {
    almaSettings: {},
    selectedFeePlan: null,
    isLoading: false,
};

const actions = {
    setAlmaSettings(data) {
        console.log('set settings in store:', data);
        return {
            type: 'SET_ALMA_SETTINGS',
            payload: data,
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
                almaSettings: action.payload,
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
            return state;
    }
}

const selectors = {
    getAlmaSettings(state) {
        return state.almaSettings || {};
    },
    getSelectedFeePlan(state) {
        return state.selectedFeePlan;
    },
    isLoading(state) {
        return state.isLoading;
    }
};

registerStore(
    'alma/alma-store',
    {
        reducer,
        actions,
        selectors,
    }
);
