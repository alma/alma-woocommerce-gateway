import {registerStore} from '@wordpress/data';


const DEFAULT_STATE = {
	almaEligibility: {},
	selectedFeePlan: null,
	isLoading: false,
};

const actions = {
	setAlmaEligibility( data ) {
		return {
			type: 'SET_ALMA_ELIGIBILITY',
			payload: data,
		};
	},
	setSelectedFeePlan( plan ) {
		return {
			type: 'SET_SELECTED_FEE_PLAN',
			payload: plan,
		};
	},
	setLoading( isLoading ) {
		return {
			type: 'SET_LOADING',
			payload: isLoading,
		};
	},
};

function reducer(state = DEFAULT_STATE, action) {
	switch (action.type) {
		case 'SET_ALMA_ELIGIBILITY':
			return {
				...state,
				almaEligibility: action.payload,
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
	getAlmaEligibility( state ) {
		return state.almaEligibility;
	},
	getSelectedFeePlan( state ) {
		return state.selectedFeePlan;
	},
	isLoading( state ) {
		return state.isLoading;
	},
};

const store = registerStore(
	'alma/alma-store',
	{
		reducer,
		actions,
		selectors,
	}
);

export default store;
