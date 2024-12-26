import {registerStore} from '@wordpress/data';


const DEFAULT_STATE = {
    almaEligibility: {},
    isLoading: false,
};

const actions = {
    setAlmaEligibility(data) {
        return {
            type: 'SET_ALMA_ELIGIBILITY',
            payload: data,
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
        case 'SET_ALMA_ELIGIBILITY':
            console.log('SET_ALMA_ELIGIBILITY')
            return {
                ...state,
                almaEligibility: action.payload,
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
    getAlmaEligibility(state) {
        return state.almaEligibility;
    },
    isLoading(state) {
        return state.isLoading;
    },
};

const store = registerStore('alma/alma-store', {
    reducer,
    actions,
    selectors,
});

export default store;
