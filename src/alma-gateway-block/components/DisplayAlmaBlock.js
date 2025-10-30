import {useSelect} from '@wordpress/data';
import {useEffect, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaBlock = (props) => {
    const {
        eventRegistration,
        emitResponse,
        gateway,
        storeKey,
        cartTotal,
    } = props;
    const {onPaymentSetup} = eventRegistration;

    const {almaSettings, gatewaySettings, isLoading} = useSelect(
        (select) => ({
            almaSettings: select(storeKey).getAlmaSettings(),
            gatewaySettings: select(storeKey).getGatewaySettings(gateway),
            isLoading: select(storeKey).isLoading()
        }), []
    );

    // Define default plan and selected plan outside the render return
    const availableFeePlans = gatewaySettings.fee_plans_settings || {};

    let default_plan = '';
    if (!isLoading && Object.keys(availableFeePlans || {}).length > 0) {
        default_plan = Object.keys(availableFeePlans)[0];
    }
    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);

    const plan = !isLoading
        ? availableFeePlans?.[selectedFeePlan] ?? availableFeePlans?.[default_plan]
        : null;

    // Always define useEffect, regardless of `isLoading`
    useEffect(() => {
        if (isLoading || !plan) return; // Skip if still loading or no plan

        const unsubscribe = onPaymentSetup(() => {
            const nonceKey = `alma_checkout_nonce${gatewaySettings.gateway_name}`;
            const paymentMethodData = {
                [nonceKey]: `${almaSettings.nonce_value}`,
                alma_fee_plan: plan.planKey,
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
    }, [availableFeePlans, onPaymentSetup, selectedFeePlan, plan, isLoading]);

    return isLoading ? <div></div> : <AlmaBlock
        hasInPage={almaSettings.is_in_page}
        isPayNow={gatewaySettings.is_pay_now}
        totalPrice={cartTotal}
        gatewaySettings={gatewaySettings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={availableFeePlans}
    />

};
