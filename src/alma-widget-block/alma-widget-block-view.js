(function ($) {
    $(document).ready(() => {

        const data = window.wc.wcSettings.getSetting(`alma-widget-block_data`, null);
        let widget = Alma.Widgets.initialize(
            data.merchant_id, // ID marchand
            data.environment, // mode de l'API (LIVE ou TEST)
        );
        console.log(widget);
        widget.add(Alma.Widgets.PaymentPlans, {
            container: '#alma-widget',
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
        console.log(widget);
    });

})(jQuery);
