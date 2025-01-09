import {useSelect} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlocks} from "./alma-blocks-component.tsx";

export const DisplayAlmaBlocks = (props) => {
    const {eventRegistration, emitResponse, settings, gateway, store_key, isPayNow} = props;
    const {onPaymentSetup} = eventRegistration;

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
    let plan = eligibility[gateway][selectedFeePlan] ?? eligibility[gateway][default_plan]
    useEffect(
        () => {
            const unsubscribe = onPaymentSetup(
                () => {
                    const nonceKey = `alma_checkout_nonce${settings.gateway_name}`;
                    const paymentMethodData = {
                        [nonceKey]: `${settings.nonce_value}`,
                        alma_fee_plan: plan.planKey,
                        payment_method: settings.gateway_name,
                    }

                    return {
                        type: emitResponse.responseTypes.SUCCESS,
                        meta: {
                            paymentMethodData
                        }
                    };
                }
            );
            // Unsubscribes when this component is unmounted.
            return () => {
                unsubscribe();
            };
        },
        [
            eligibility,
            onPaymentSetup,
            selectedFeePlan
        ]
    );

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