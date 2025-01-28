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

    waitAlmaWidgetDiv(almaWidgetDivId).then(() => {
        const data = window.wc.wcSettings.getSetting(`alma-widget-block_data`, null);
        let widget = Alma.Widgets.initialize(
            data.merchant_id,
            Alma.ApiMode[data.environment],
        );

        widget.add(Alma.Widgets.PaymentPlans, {
            container: almaWidgetDivId,
            purchaseAmount: 45000,
            locale: 'fr',
            hideIfNotEligible: false,
            plans: [
                {
                    installmentsCount: 1,
                    deferredDays: 30,
                    minAmount: 5000,
                    maxAmount: 50000,
                },
                {
                    installmentsCount: 3,
                    minAmount: 5000,
                    maxAmount: 50000,
                },
                {
                    installmentsCount: 4,
                    minAmount: 5000,
                    maxAmount: 50000,
                },
            ],
        });
    });
})(jQuery);

