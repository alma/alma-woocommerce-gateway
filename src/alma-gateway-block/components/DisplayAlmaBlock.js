import {select, useSelect} from '@wordpress/data';
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

    // Define onPaymentProcessing effect
    const {onPaymentProcessing} = eventRegistration;

    useEffect(() => {
        console.log('Special use effet selectedFeePlan changed:', selectedFeePlan);
    }, [selectedFeePlan]);

    useEffect(() => {
        console.log('selectedFeePlan changed:', selectedFeePlan);
        const unsubscribe = onPaymentProcessing(async () => {
            if (almaSettings.is_in_page) {
                return handleInPagePayment(gatewaySettings, emitResponse, selectedFeePlan);
            } else {
                return handleStandardPayment(gatewaySettings, emitResponse, selectedFeePlan);
            }
        });

        return () => unsubscribe();
    }, [onPaymentProcessing, selectedFeePlan]);

    /**
     * Handle Classic Payment
     *
     * @param gatewaySettings
     * @param emitResponse
     * @param selectedFeePlan
     * @returns {Promise<{type: *, meta: {paymentMethodData: {alma_plan_key: string, payment_method: string}}}>}
     */
    const handleStandardPayment = (gatewaySettings, emitResponse, selectedFeePlan) => {

        console.log('Redirect to Alma Checkout', selectedFeePlan);

        const nonceKey = almaSettings.nonce_key;
        const paymentMethodData = {
            [nonceKey]: `${almaSettings.nonce_value}`,
            alma_plan_key: String(selectedFeePlan || ''),
        };

        return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
                paymentMethodData: paymentMethodData
            }
        };
    };

    /**
     * Handle In-Page Payment
     *
     * @param gatewaySettings
     * @param emitResponse
     * @param selectedFeePlan
     * @returns {Promise<{type: *, meta: {paymentMethodData: {alma_plan_key: string, payment_method: string}}}>}
     */
    const handleInPagePayment = async (gatewaySettings, emitResponse, selectedFeePlan) => {

        console.log('In Page Checkout');
        const selectedPlan = select(storeKey).getSelectedFeePlan();
        console.log('Selected plan:', selectedFeePlan);

        const paymentMethodData = {
            alma_plan_key: String(selectedFeePlan || ''),
            payment_method: String(gatewaySettings.gateway_name || ''),
        };

        return {
            type: emitResponse.responseTypes.SUCCESS,
            meta: {
                paymentMethodData: paymentMethodData
            }
        };
    };

    return isLoading ? <div></div> : <AlmaBlock
        hasInPage={almaSettings.is_in_page}
        totalPrice={cartTotal}
        gatewaySettings={gatewaySettings}
        selectedFeePlan={plan.planKey}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={availableFeePlans}
    />

};
