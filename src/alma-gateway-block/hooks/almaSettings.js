import {dispatch} from '@wordpress/data';

/**
 * Fetch Alma Settings including Eligibility from the server
 *
 * @param storeKey - The Redux store key
 * @param url - The API endpoint URL
 * @returns {Promise<void>}
 */
export const fetchAlmaSettings = async (storeKey, url) => {

    console.log(`Fetching Alma settings from ${url}...`);

    dispatch(storeKey).setLoading(true);
    try {
        const response = await fetch(
            url,
            {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            }
        );
        const almaSettings = await response.json()

        if (almaSettings.success) {
            dispatch(storeKey).setAlmaSettings(almaSettings);
        }

    } catch (error) {
        console.error('Erreur lors de l\'appel API :', error);
    } finally {
        dispatch(storeKey).setLoading(false);
    }
}
