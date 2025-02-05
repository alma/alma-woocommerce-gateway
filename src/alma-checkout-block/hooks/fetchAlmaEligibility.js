import {dispatch} from '@wordpress/data';

/**
 * @param storeKey
 * @param url
 * @returns {Promise<void>}
 */
export const fetchAlmaEligibility = async (storeKey, url) => {
    dispatch(storeKey).setLoading(true);
    try {
        const response = await fetch(
            url,
            {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
            }
        );
        const data = await response.json()
        if (data.success) {
            dispatch(storeKey).setAlmaEligibility(data.eligibility);
        }
    } catch (error) {
        console.error('Erreur lors de l’appel API :', error);
    } finally {
        dispatch(storeKey).setLoading(false);
    }
}
