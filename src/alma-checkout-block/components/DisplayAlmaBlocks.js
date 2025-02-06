import {useSelect} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlocks} from "./alma-blocks-component.tsx";

export const DisplayAlmaBlocks = (props) => {
    const {eventRegistration, emitResponse, settings, gateway, store_key, isPayNow} = props;
    const {onPaymentSetup} = eventRegistration;

    const {CART_STORE_KEY} = window.wc.wcBlocksData;

    const {cartTotal} = useSelect((select) => ({
        cartTotal: select(CART_STORE_KEY).getCartTotals()
    }), []);

    const {eligibility, isLoading} = useSelect(
        (select) => ({
            eligibility: select(store_key).getAlmaEligibility(),
            isLoading: select(store_key).isLoading()
        }), []
    );


    // Define default plan and selected plan outside of the render return
    let default_plan = '';
    if (!isLoading && Object.keys(eligibility[gateway] || {}).length > 0) {
        default_plan = Object.keys(eligibility[gateway])[0];
    }
    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);

    const plan = !isLoading
        ? eligibility[gateway]?.[selectedFeePlan] ?? eligibility[gateway]?.[default_plan]
        : null;

    // Always define useEffect, regardless of `isLoading`
    useEffect(() => {
        if (isLoading || !plan) return; // Skip if still loading or no plan

        const unsubscribe = onPaymentSetup(() => {
            const nonceKey = `alma_checkout_nonce${settings.gateway_name}`;
            const paymentMethodData = {
                [nonceKey]: `${settings.nonce_value}`,
                alma_fee_plan: plan.planKey,
                payment_method: settings.gateway_name,
            };

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {paymentMethodData},
            };
        });

        // Cleanup when component unmounts
        return () => {
            unsubscribe();
        };
    }, [eligibility, onPaymentSetup, selectedFeePlan, plan, isLoading]);

    return isLoading ? <div></div> : <AlmaBlocks
        hasInPage={settings.is_in_page}
        isPayNow={isPayNow}
        totalPrice={cartTotal.total_price}
        settings={settings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={eligibility[gateway]}
    />

};
