import {dispatch, useSelect} from '@wordpress/data';
import {useCallback, useEffect, useRef, useState} from '@wordpress/element';
import {AlmaBlock} from "./alma-block-component.tsx";

export const DisplayAlmaInPageBlock = (props) => {
    const {
        eventRegistration,
        emitResponse,
        gateway,
        storeKey,
        cartTotal,
        setInPage,
    } = props;

    const {onPaymentSetup, onCheckoutSuccess, onCheckoutFail} = eventRegistration;

    // ✅ Accéder au store CHECKOUT pour réinitialiser l'état
    const {CHECKOUT_STORE_KEY} = window.wc.wcBlocksData;

    const {almaSettings, gatewaySettings, isLoading} = useSelect(
        (select) => ({
            almaSettings: select(storeKey).getAlmaSettings(),
            gatewaySettings: select(storeKey).getGatewaySettings(gateway),
            isLoading: select(storeKey).isLoading()
        }), []
    );

    // ✅ Utiliser useRef pour conserver l'instance inPage
    const inPageRef = useRef(null);
    const [isInPageReady, setIsInPageReady] = useState(false);
    const [isProcessingPayment, setIsProcessingPayment] = useState(false);

    // Define default plan and selected plan
    const availableFeePlans = gatewaySettings.fee_plans_settings || {};

    let default_plan = '';
    if (!isLoading && Object.keys(availableFeePlans || {}).length > 0) {
        default_plan = Object.keys(availableFeePlans)[0];
    }

    const [selectedFeePlan, setSelectedFeePlan] = useState(default_plan);

    const plan = !isLoading
        ? availableFeePlans?.[selectedFeePlan] ?? availableFeePlans?.[default_plan]
        : null;

    // ✅ Synchroniser le plan sélectionné avec le store
    useEffect(() => {
        if (selectedFeePlan) {
            dispatch(storeKey).setSelectedFeePlan(selectedFeePlan);
        }
    }, [selectedFeePlan]);

    /**
     * ✅ Fonction pour réinitialiser le checkout après fermeture/annulation
     */
    const resetCheckoutState = useCallback(() => {
        console.log('🔄 ' + gatewaySettings.name + ': Resetting checkout state...');

        try {
            // ✅ Réinitialiser l'état "processing" du checkout
            dispatch(CHECKOUT_STORE_KEY).__internalSetProcessing(false);

            // ✅ Réinitialiser l'état "idle" du checkout
            dispatch(CHECKOUT_STORE_KEY).__internalSetIdle();

            console.log('✅ ' + gatewaySettings.name + ': Checkout state reset successfully');
        } catch (error) {
            console.warn('⚠️ Could not reset checkout state:', error);
        }

        setIsProcessingPayment(false);
    }, [CHECKOUT_STORE_KEY]);

    const initializeInpage = useCallback((total_price) => {
        console.log('🔧 Initializing Alma In-Page Iframe...');

        // ❌ NE PAS unmount si un paiement est en cours !
        if (isProcessingPayment) {
            console.log('⚠️ Payment in progress, skipping re-initialization');
            return;
        }

        // Nettoyer l'instance précédente si elle existe
        if (inPageRef.current && typeof inPageRef.current.unmount === 'function') {
            try {
                console.log('✅ Unmounting previous instance');
                inPageRef.current.unmount();
            } catch (e) {
                console.warn('❌ Failed to unmount:', e);
            }
        }

        if (!plan) {
            console.warn('⚠️ No plan available');
            return;
        }

        console.log('Alma.InPage:', Alma?.InPage);
        console.log('merchantId:', almaSettings.merchant_id);
        console.log('amountInCents:', total_price);
        console.log('installmentsCount:', plan.installmentsCount);
        console.log('selector:', "#alma-inpage-container");
        console.log('deferredDays:', plan.deferredDays);
        console.log('deferredMonths:', plan.deferredMonths);
        console.log('environment:', almaSettings.environment);
        console.log('locale:', almaSettings.language);

        try {
            inPageRef.current = Alma.InPage.initialize({
                merchantId: almaSettings.merchant_id,
                amountInCents: total_price,
                installmentsCount: plan.installmentsCount,
                selector: "#alma-inpage-container",
                deferredDays: plan.deferredDays,
                deferredMonths: plan.deferredMonths,
                environment: almaSettings.environment,
                locale: almaSettings.language,
            });
            console.warn('InPage instance:', inPageRef.current);

            // ✅ Passer l'instance au parent
            setInPage(inPageRef.current);

            setIsInPageReady(true);
            console.log('✅ In-Page initialized');
        } catch (error) {
            console.error('❌ Failed to initialize:', error);
            setIsInPageReady(false);
        }
    }, [plan, almaSettings, setInPage, isProcessingPayment]);

    // ✅ Cleanup UNIQUEMENT quand le composant est démonté (changement de gateway)
    // useEffect(() => {
    //     return () => {
    //         console.log('🧹 Component unmounting, cleaning up InPage');
    //         if (inPageRef.current?.unmount) {
    //             try {
    //                 inPageRef.current.unmount();
    //                 setInPage(null);
    //             } catch (e) {
    //                 console.warn('Failed to cleanup:', e);
    //             }
    //         }
    //     };
    // }, [setInPage]); // ✅ Pas de dépendances sur isProcessingPayment

    /**
     * ✅ onPaymentSetup - Préparer le paiement
     */
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            console.log('🚀 ' + gatewaySettings.name + ': In-Page Payment Setup starting...');

            if (isProcessingPayment) {
                console.log('⚠️ ' + gatewaySettings.name + ': Payment already processing, skipping...');
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }

            setIsProcessingPayment(true);

            try {
                const nonceKey = almaSettings.nonce_key;
                const paymentMethodData = {
                    [nonceKey]: `${almaSettings.nonce_value}`,
                    alma_plan_key: String(selectedFeePlan || ''),
                    payment_method: String(gatewaySettings.gateway_name || ''),
                };

                console.log('📦 ' + gatewaySettings.name + ': Payment data prepared:', paymentMethodData);

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: paymentMethodData,
                    }
                };

            } catch (error) {
                console.error('❌ Payment setup error:', error);
                setIsProcessingPayment(false);
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: error.message || 'Payment initialization failed',
                };
            }
        });

        return () => unsubscribe();
    }, [onPaymentSetup, selectedFeePlan, gatewaySettings.gateway_name, almaSettings.nonce_key, almaSettings.nonce_value, emitResponse.responseTypes, isProcessingPayment]);

    /**
     * ✅ onCheckoutSuccess - Ouvrir la popup après l'appel API
     */
    useEffect(() => {
        const unsubscribeSuccess = onCheckoutSuccess(async (checkoutResponse) => {
            console.log('✅ Checkout API call successful:', checkoutResponse);

            try {
                // ✅ Récupérer le payment_id depuis la réponse
                const paymentDetails = checkoutResponse.processingResponse?.paymentDetails;
                const almaPaymentId = paymentDetails?.alma_payment_id;

                console.log('🔍 Payment details:', paymentDetails);

                if (!almaPaymentId) {
                    console.error('❌ No Alma payment ID in response');
                    console.error('Full response:', JSON.stringify(checkoutResponse, null, 2));
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment initialization failed - no payment ID',
                    };
                }

                console.log('🎯 Alma Payment ID:', almaPaymentId);
                console.log('InPage Ref:', inPageRef.current);

                if (!inPageRef.current || !isInPageReady) {
                    console.error('❌ In-Page not ready');
                    resetCheckoutState();
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Payment widget not initialized',
                    };
                }

                console.log('🪟 Opening Alma In-Page payment modal...');
                console.log(inPageRef.current)

                try {
                    const paymentResult = await inPageRef.current.startPayment({
                        paymentId: almaPaymentId,
                        onUserCloseModal: () => {
                            console.log('⚠️ User closed the payment modal');
                            resetCheckoutState();
                        }
                    });

                    console.log('✅ Payment result:', paymentResult);

                    // Vérifier le statut du résultat
                    if (paymentResult && paymentResult.status === 'success') {
                        console.log('✅ Payment completed successfully');
                        setIsProcessingPayment(false);

                        // Rediriger vers la page de succès
                        if (checkoutResponse.redirectUrl) {
                            window.location.href = checkoutResponse.redirectUrl;
                        } else {
                            const orderId = checkoutResponse.orderId || paymentResult.orderId;
                            const orderKey = checkoutResponse.orderKey || '';
                            window.location.href = `/checkout/order-received/${orderId}/?key=${orderKey}`;
                        }
                    } else if (paymentResult && paymentResult.status === 'error') {
                        console.error('❌ Payment error:', paymentResult.error);
                        resetCheckoutState();
                        alert(paymentResult.error?.message || 'Payment failed. Please try again.');
                    } else if (paymentResult && paymentResult.status === 'cancelled') {
                        console.log('⚠️ Payment cancelled by user');
                        resetCheckoutState();
                    }

                } catch (paymentError) {
                    console.error('❌ Payment modal error:', paymentError);
                    resetCheckoutState();

                    if (paymentError.code === 'user_cancelled') {
                        console.log('⚠️ User cancelled payment');
                    } else {
                        alert(paymentError.message || 'Payment failed. Please try again.');
                    }
                }

            } catch (error) {
                console.error('❌ Failed to open payment modal:', error);
                resetCheckoutState();
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Failed to open payment popup: ' + error.message,
                };
            }
        });

        return () => unsubscribeSuccess();
    }, [onCheckoutSuccess, isInPageReady, emitResponse.responseTypes, resetCheckoutState]);

    /**
     * ✅ Initialiser In-Page (widget informatif) quand le plan change
     */
    const lastInitRef = useRef({planKey: null, cartTotal: null});

    useEffect(() => {
        // Ne réinitialiser que si le plan ou le montant a vraiment changé
        if (!isLoading && plan && cartTotal) {
            const planKey = plan.planKey;

            if (lastInitRef.current.planKey !== planKey || lastInitRef.current.cartTotal !== cartTotal) {
                console.log('🔄 Plan or cart changed, reinitializing In-Page widget');
                lastInitRef.current = {planKey, cartTotal};
                initializeInpage(cartTotal);
            }
        }
    }, [plan?.planKey, cartTotal, isLoading, initializeInpage]);

    const displayInstallments = gatewaySettings.is_pay_now ? 'none' : 'block';

    return isLoading ? (
        <div>Loading payment options...</div>
    ) : (
        <>
            <AlmaBlock
                hasInPage={almaSettings.is_in_page}
                isPayNow={gatewaySettings.is_pay_now}
                totalPrice={cartTotal}
                gatewaySettings={gatewaySettings}
                selectedFeePlan={plan.planKey}
                setSelectedFeePlan={setSelectedFeePlan}
                plans={availableFeePlans}
            />
            {/* ✅ Widget informatif In-Page */}
            <div
                id="alma-inpage-container"
                style={{display: displayInstallments}}
            ></div>
            {/* ✅ Indicateur de traitement */}
            {isProcessingPayment}
        </>
    );
}