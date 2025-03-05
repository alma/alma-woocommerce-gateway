import {useSelect, dispatch} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlocks} from "./alma-blocks-component.tsx";

export const DisplayAlmaInPageBlocks = (props) => {
    const {settings, gateway, store_key, setInPage, isPayNow} = props;

    const {CART_STORE_KEY} = window.wc.wcBlocksData
    const {cartTotal} = useSelect((select) => ({
        cartTotal: select(CART_STORE_KEY).getCartTotals()
    }), []);

    const {eligibility, eligibilityCartTotal, isLoading} = useSelect(
        (select) => ({
            eligibility: select(store_key).getAlmaEligibility(),
            eligibilityCartTotal: select(store_key).getCartTotal(),
            isLoading: select(store_key).isLoading()
        }), []
    );

    // Init default plan
    let default_plan = ''

    if (!isLoading && Object.keys(eligibility[gateway]).length > 0) {
        // default plan is the first plan
        default_plan = Object.keys(eligibility[gateway])[0]
    }

    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);
    const plan = !isLoading
        ? eligibility[gateway]?.[selectedFeePlan] ?? eligibility[gateway]?.[default_plan]
        : null;

    useEffect(() => {
        // Transfer the selected fee plan to the alma redux store
        dispatch(store_key).setSelectedFeePlan(selectedFeePlan);

    }, [selectedFeePlan])


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
            amountInCents: total_price * 100,
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
        if (!isLoading && plan) {
            setSelectedFeePlan(plan.planKey);
            initializeInpage(settings, eligibilityCartTotal)
        }
    }, [selectedFeePlan, cartTotal, isLoading])

    const displayInstallments = isPayNow ? 'none' : 'block';
    return isLoading ? <div></div> : <><AlmaBlocks
        hasInPage={settings.is_in_page}
        isPayNow={isPayNow}
        totalPrice={eligibilityCartTotal}
        settings={settings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={eligibility[gateway]}
    />
        <div id="alma-inpage-alma_in_page" style={{display: displayInstallments}}></div>
    </>

}
