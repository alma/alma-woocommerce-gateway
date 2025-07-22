(function ($) {
    console.log('hello script');
    $(function () {
        var widgets = Alma.Widgets.initialize(
            'merchant_11mLCKp39by3Yb1VAAIAWWqSwYg8Q2Fy17', // ID marchand
            Alma.ApiMode.TEST, // mode de l'API (LIVE ou TEST)
        )

        widgets.add(Alma.Widgets.PaymentPlans, {
            container: '.alma_widget',
            purchaseAmount: alma_widget_settings.price,
            locale: 'fr',
            hideIfNotEligible: false,
            transitionDelay: 5500,
            monochrome: false,
            hideBorder: false,
            plans: [
                {
                    installmentsCount: 3,
                    minAmount: 8000,
                    maxAmount: 200000
                },
                {
                    installmentsCount: 4,
                    minAmount: 15000,
                    maxAmount: 200000
                }
            ]
        })

    })
})(jQuery);
