import {useEffect} from '@wordpress/element';
import {useSelect} from '@wordpress/data';

const AlmaWidget = () => {
    if (!window.wc.wcSettings.getSetting(`alma-widget-block_data`, null).can_be_displayed) {
        return null;
    }
    const {CART_STORE_KEY} = window.wc.wcBlocksData

    const total = useSelect((select) => {
        const cart = select(CART_STORE_KEY);
        return cart ? cart.getCartTotals()?.total_price || 0 : 0;
    }, []);

    useEffect(() => {
        if (!total) return;

        const almaWidgetDivId = "alma-widget";

        function addAlmaWidget() {
            const data = window.wc.wcSettings.getSetting(`alma-widget-block_data`, null);
            if (!data) return;

            let widget = Alma.Widgets.initialize(
                data.merchant_id,
                Alma.ApiMode[data.environment],
            );

            widget.add(Alma.Widgets.PaymentPlans, {
                container: '#' + almaWidgetDivId,
                purchaseAmount: total,
                locale: data.locale,
                hideIfNotEligible: false,
                plans: data.plans.map(plan => ({
                    installmentsCount: plan.installments_count,
                    minAmount: plan.min_amount,
                    maxAmount: plan.max_amount,
                    deferredDays: plan.deferred_days,
                    deferredMonths: plan.deferred_months
                })),
            });
        }

        if (window.Alma && window.wc) {
            addAlmaWidget();
        }
    }, [total]);

    return <div id="alma-widget"></div>;
};

export default AlmaWidget;
