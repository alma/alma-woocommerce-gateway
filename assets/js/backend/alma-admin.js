/* global woocommerce_admin */
(function ($) {
    $(function () {
        // Toggle fee plans on/off.
        $('.wc_gateways').on(
            'click',
            '.wc-alma-toggle-fee-plan-enabled',
            function () {
                const click = $(this),
                    row = click.closest('tr'),
                    toggle = row.find('.woocommerce-input-toggle'),
                    hiddenInput = row.find('input[type="hidden"]');

                // Loading state
                toggle.addClass('woocommerce-input-toggle--loading');

                // What's the current state?
                const isEnabled = hiddenInput.val();
                const newValue = isEnabled === '1' ? '0' : '1';

                // Toggle state
                if ('1' === newValue) {
                    toggle.removeClass(
                        'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled'
                    );
                    toggle.addClass(
                        'woocommerce-input-toggle--enabled'
                    );
                    toggle.removeClass(
                        'woocommerce-input-toggle--loading'
                    );
                } else {
                    toggle.removeClass(
                        'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled'
                    );
                    toggle.addClass(
                        'woocommerce-input-toggle--disabled'
                    );
                    toggle.removeClass(
                        'woocommerce-input-toggle--loading'
                    );
                }
                hiddenInput.val(newValue);
                $(".woocommerce-save-button").removeAttr("disabled");

                return false;
            }
        );
    });

})(jQuery);


add_button_show_pwd('woocommerce_alma_config_gateway_live_api_key');
add_button_show_pwd('woocommerce_alma_config_gateway_test_api_key');

function add_button_show_pwd(button_name) {
    var button = jQuery('#' + button_name);
    button.after('<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Show password" onclick="toggle_pwd_field(\'' + button_name + '\')"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button>')

}

function toggle_pwd_field(field) {
    var pass_id = jQuery('input[name="' + field + '"]').attr("id");
    var x = document.getElementById(pass_id);
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}