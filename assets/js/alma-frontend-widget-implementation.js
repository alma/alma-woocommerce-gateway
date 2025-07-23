(function ($) {
    $(function () {
        let containerSelector = alma_widget_settings.widget_selector;
        if ($(containerSelector).length === 0) {
            containerSelector = alma_widget_settings.widget_default_selector;
        }

        const widgets = Alma.Widgets.initialize(
            alma_widget_settings.merchant_id,
            Alma.ApiMode.TEST,
        );

        widgets.add(Alma.Widgets.PaymentPlans, {
            container: containerSelector,
            purchaseAmount: alma_widget_settings.price,
            locale: alma_widget_settings.language,
            hideIfNotEligible: alma_widget_settings.hide_if_not_eligible,
            transitionDelay: alma_widget_settings.transition_delay,
            monochrome: alma_widget_settings.monochrome,
            hideBorder: alma_widget_settings.hide_border,
            plans: alma_widget_settings.fee_plan_list
        })

    })
})(jQuery);
