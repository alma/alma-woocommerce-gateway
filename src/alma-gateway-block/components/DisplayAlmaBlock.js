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
    // WC Blocks API compatibility:
    // WC 10+ (Blocks 9+): onPaymentSetup
    // WC 9.x (Blocks 7-8): onPaymentProcessing
    const onPaymentSetup = eventRegistration.onPaymentSetup || eventRegistration.onPaymentProcessing;

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

    // Sync selectedFeePlan when default_plan becomes available after loading
    useEffect(() => {
        if (default_plan && !selectedFeePlan) {
            setSelectedFeePlan(default_plan);
        }
    }, [default_plan]);

    const plan = !isLoading
        ? availableFeePlans?.[selectedFeePlan] ?? availableFeePlans?.[default_plan]
        : null;

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            return handleStandardPayment(gatewaySettings, emitResponse, selectedFeePlan);
        });

        return () => unsubscribe();
    }, [onPaymentSetup, selectedFeePlan]);

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

    // WC 10.7+ mounts the gateway content earlier in the page lifecycle, so on
    // first render the cart-update fetch has not yet populated `fee_plans_settings`
    // and `plan` is undefined. Reading `plan.planKey` then crashes the React tree
    // (`TypeError: Cannot read properties of undefined (reading 'planKey')`).
    // Wait until at least one eligible plan is available before rendering AlmaBlock.
    const hasPlans = Object.keys( availableFeePlans ).length > 0;
    return ( isLoading || ! hasPlans ) ? <div></div> : <AlmaBlock
        hasInPage={almaSettings.is_in_page}
        totalPrice={cartTotal}
        gatewaySettings={gatewaySettings}
        selectedFeePlan={plan?.planKey ?? default_plan}
        setSelectedFeePlan={setSelectedFeePlan}
        plans={availableFeePlans}
        locale={almaSettings.language || 'fr'}
    />

};
