import {useSelect, dispatch} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlocks} from "./alma-blocks-component.tsx";

export const DisplayAlmaInPageBlocks = (props) => {
    const {settings, gateway, store_key, setInPage} = props;

    const {CART_STORE_KEY} = window.wc.wcBlocksData
    const {cartTotal} = useSelect((select) => ({
        cartTotal: select(CART_STORE_KEY).getCartTotals()
    }), []);

    const {eligibility, isLoading} = useSelect(
        (select) => ({
            eligibility: select(store_key).getAlmaEligibility(),
            isLoading: select(store_key).isLoading()
        }), []
    );

    // Init default plan
    let default_plan = ''

    if (Object.keys(eligibility[gateway]).length > 0) {
        // default plan is the first plan
        default_plan = Object.keys(eligibility[gateway])[0]
    }

    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);

    useEffect(() => {
        // Transfer the selected fee plan to the alma redux store
        dispatch(store_key).setSelectedFeePlan(selectedFeePlan);

    }, [selectedFeePlan])

    let plan = eligibility[gateway][selectedFeePlan] ?? eligibility[gateway][default_plan]

    let inPage = undefined;

    function initializeInpage(settingsInPage, total_price) {
        if (
            inPage !== undefined
            && document.getElementById('alma-embedded-iframe') !== null
        ) {
            inPage.unmount();
        }
        inPage = Alma.InPage.initialize({
            merchantId: settingsInPage.merchant_id,
            amountInCents: total_price,
            installmentsCount: plan.installmentsCount,
            selector: "#alma-inpage-alma_in_page",
            deferredDays: plan.deferredDays,
            deferredMonths: plan.deferredMonths,
            environment: settingsInPage.environment,
            locale: settingsInPage.locale,
        });
        setInPage(inPage);
    }

    useEffect(() => {
        if (!isLoading) {
            initializeInpage(settings, cartTotal.total_price)
        }
    }, [selectedFeePlan, cartTotal, isLoading])


    return isLoading ? <div></div> : <><AlmaBlocks
        hasInPage={settings.is_in_page}
        totalPrice={cartTotal.total_price}
        settings={settings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={eligibility[gateway]}
    />
        <div id="alma-inpage-alma_in_page"></div>
    </>

}
