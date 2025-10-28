import {dispatch, useSelect} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaInPageBlock = (props) => {
    const {gatewaySettings, gateway, store_key, setInPage, isPayNow} = props;

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
            initializeInpage(gatewaySettings, eligibilityCartTotal)
        }
    }, [selectedFeePlan, cartTotal, isLoading])

    const displayInstallments = isPayNow ? 'none' : 'block';
    return isLoading ? <div></div> : <><AlmaBlock
        hasInPage={gatewaySettings.is_in_page}
        isPayNow={isPayNow}
        totalPrice={eligibilityCartTotal}
        gatewaySettings={gatewaySettings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={eligibility[gateway]}
    />
        <div id="alma-inpage-alma_in_page" style={{display: displayInstallments}}></div>
    </>

}
