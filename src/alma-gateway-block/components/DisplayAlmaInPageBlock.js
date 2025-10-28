import {dispatch, useSelect} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaInPageBlock = (props) => {
    const {gatewaySettings, gateway, storeKey, setInPage, isPayNow} = props;

    const {CART_STORE_KEY} = window.wc.wcBlocksData
    const {cartTotal} = useSelect((select) => ({
        cartTotal: select(CART_STORE_KEY).getCartTotals()
    }), []);

    const {almaSettings, isLoading} = useSelect(
        (select) => ({
            almaSettings: select(storeKey).getAlmaSettings(),
            isLoading: select(storeKey).isLoading()
        }), []
    );

    // Define default plan and selected plan outside the render return
    const availableFeePlans = almaSettings?.gateway_settings[gateway].fee_plans_settings || {};

    // Init default plan
    let default_plan = ''

    if (!isLoading && Object.keys(availableFeePlans || {}).length > 0) {
        default_plan = Object.keys(availableFeePlans)[0]
    }

    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);
    const plan = !isLoading
        ? availableFeePlans?.[selectedFeePlan] ?? availableFeePlans?.[default_plan]
        : null;

    useEffect(() => {
        // Transfer the selected fee plan to the alma redux store
        dispatch(storeKey).setSelectedFeePlan(selectedFeePlan);

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
            initializeInpage(gatewaySettings, cartTotal)
        }
    }, [selectedFeePlan, cartTotal, isLoading])

    const displayInstallments = isPayNow ? 'none' : 'block';
    return isLoading ? <div></div> : <><AlmaBlock
        hasInPage={gatewaySettings.is_in_page}
        isPayNow={isPayNow}
        totalPrice={cartTotal}
        gatewaySettings={gatewaySettings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={gatewaySettings}
    />
        <div id="alma-inpage-alma_in_page" style={{display: displayInstallments}}></div>
    </>

}
