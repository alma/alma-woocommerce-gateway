(function ($) {
    const almaWidgetDivId = '#alma-widget';

    function waitAlmaWidgetDiv(selector) {
        return new Promise((resolve) => {
            const observer = new MutationObserver(() => {
                if ($(selector).length > 0) {
                    resolve($(selector));
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {childList: true, subtree: true});
        });
    }

    function addAlmaWidget() {
        waitAlmaWidgetDiv(almaWidgetDivId).then(() => {
            const data = window.wc.wcSettings.getSetting(`alma-widget-block_data`, null);
            let widget = Alma.Widgets.initialize(
                data.merchant_id,
                Alma.ApiMode[data.environment],
            );

            widget.add(Alma.Widgets.PaymentPlans, {
                container: almaWidgetDivId,
                purchaseAmount: data.amount,
                locale: data.locale,
                hideIfNotEligible: false,
                plans: data.plans.map(
                    function (plan) {
                        return {
                            installmentsCount: plan.installments_count,
                            minAmount: plan.min_amount,
                            maxAmount: plan.max_amount,
                            deferredDays: plan.deferred_days,
                            deferredMonths: plan.deferred_months
                        }
                    }
                ),
            });
        });
    }

    addAlmaWidget();
})(jQuery);

