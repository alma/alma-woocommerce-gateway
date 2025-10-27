import {select, useDispatch, useSelect} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaBlock = (props) => {
    console.log("FUS")

    const {CART_STORE_KEY} = window.wc.wcBlocksData
    const cartStore = select(CART_STORE_KEY);
    const almaStore = select(storeKey);
    console.log('---------> gatewaySettings', gatewaySettings)

    const {eventRegistration, emitResponse, almaSettings, gatewaySettings, storeKey} = props;
    const {onPaymentSetup} = eventRegistration;
    const dispatch = useDispatch(storeKey);
    const {isLoading, cartTotal} = useSelect(
        (select) => ({
            cartTotal: cartStore.getCartTotals().total_price,
            isLoading: almaStore.isLoading()
        }),
        []
    );

    console.log('---------> gatewaySettings', gatewaySettings)

    // Define default plan and selected plan outside the render return
    let default_plan = '';
    if (!isLoading && gatewaySettings && Object.keys(gatewaySettings).length > 0) {
        default_plan = Object.keys(gatewaySettings)[0];
    }
    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);

    // Update the store when selectedFeePlan changes
    useEffect(() => {
        if (selectedFeePlan) {
            dispatch.setSelectedFeePlan(selectedFeePlan);
        }
    }, [selectedFeePlan, dispatch]);

    // Set initial default plan in store
    useEffect(() => {
        if (default_plan && !selectedFeePlan) {
            setSelectedFeePlan(default_plan);
        }
    }, [default_plan]);

    const plan = !isLoading && gatewaySettings
        ? gatewaySettings?.[selectedFeePlan] ?? gatewaySettings?.[default_plan]
        : null;

    // Always define useEffect, regardless of `isLoading`
    useEffect(() => {
        if (isLoading || !plan) return; // Skip if still loading or no plan

        const unsubscribe = onPaymentSetup(() => {
            const nonceKey = `alma_checkout_nonce${gatewaySettings.gateway_name}`;
            const paymentMethodData = {
                [nonceKey]: `${almaSettings.nonce_value}`,
                alma_plan_key: String(plan.planKey || ''),
                payment_method: gatewaySettings.gateway_name,
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
    }, [gatewaySettings, onPaymentSetup, selectedFeePlan, plan, isLoading]);

    return isLoading || !gatewaySettings ? <div></div> : <AlmaBlock
        hasInPage={almaSettings.is_in_page}
        isPayNow={gatewaySettings.is_pay_now}
        totalPrice={cartTotal}
        gatewaySettings={gatewaySettings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={gatewaySettings.fee_plans_settings}
    />

};
