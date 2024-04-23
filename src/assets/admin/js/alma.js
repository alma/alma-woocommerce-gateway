/**
 * Admin page.
 *
 *
 * @param global alma_admin_params alma_admin_params params.
 *
 */
jQuery(document).ready(function () {

    almaAdminInternationalization();

    var almaAdminGeneralHelper = new AlmaAdminHelper();
    var almaAdminFeePlan = new AlmaAdminFeePlan(almaAdminGeneralHelper);
    almaAdminFeePlan.renderFeePlan();
    almaAdminFeePlan.initiateAlmaSelectMenuBehaviour();
    almaAdminFeePlan.listenFeePlanCheckboxStatus();
    almaAdminFeePlan.checkInputsOnSubmitActionTriggered();
    almaAdminGeneralHelper.toggleTechnicalConfigFields();

    var checkboxBlock = jQuery('#woocommerce_alma_use_blocks_template');
    var modal = '<div id="info-checkbox" class="notice notice-warning is-dismissible">' +
        '<p>' + alma_admin_params.block_confirmation+ ' </p>' +
        '</div>'

    checkboxBlock.on("click", function (e) {
        isChecked = document.getElementById('woocommerce_alma_use_blocks_template').checked
        infoCheckbox = document.getElementById("info-checkbox")

        if(null ===  infoCheckbox && isChecked) {
            checkboxBlock.before(modal);
        }

        if(null !== infoCheckbox && !isChecked) {
            infoCheckbox.remove()
        }
    });
});

/**
 * Alma admin General Helpers.
 */
function AlmaAdminHelper() {
    return {
        /**
         * Check if number input is between its min & max attributes.
         *
         * @param {jQuery} $input A HTML DOM Element.
         * @return {boolean}
         */
        isBetweenMinMax: function ($input) {
            var val = parseInt($input.val());
            var min = parseInt($input.attr('min'));
            var max = parseInt($input.attr('max'));
            return (val >= min && val <= max)
        },
        /**
         * Scroll to Alma section.
         *
         * @param {string} plan A fee plan.
         */
        scrollToSection: function (plan) {
            var $section = jQuery('#woocommerce_alma_' + plan + "_section");
            var keyframes = {
                scrollTop: $section.offset().top - 100 // wc admin have a top bar fixed => -100px
            };
            jQuery([document.documentElement, document.body]).animate(keyframes, 500);
        },
        /**
         * Disable or enable menuItem Option depending on the checkbox state.
         *
         * @param {jQuery} $checkbox A jQuery checkbox item.
         * @param {jQuery} $items A set of checkbox items.
         */
        toggleItems: function ($checkbox, $items) {
            if ($checkbox.is(':checked')) {
                $items.addClass('alma_option_enabled').removeClass('alma_option_disabled');
            } else {
                $items.removeClass('alma_option_enabled').addClass('alma_option_disabled');
            }
        },
        /**
         * Show or hide technical fields.
         */
        toggleTechnicalConfigFields: function () {
            jQuery('#alma_link_toggle_technical_section').parent('div').next('table.form-table').toggle();
            jQuery(document).on('click', '#alma_link_toggle_technical_section', function (e) {
                e.preventDefault();
                jQuery(this).parent('div').next('table.form-table').toggle('slow');
            });
        }
    }
}

/**
 * Alma admin Internationalization helper.
 */
function almaAdminInternationalization() {
    jQuery(document).on('change', '.list_lang_title', function (e) {
        e.preventDefault();
        var codeLang = jQuery(this).val();
        jQuery('.list_lang_title option[value=' + codeLang + ']').prop('selected', true);
        var $rowParents = jQuery('.alma-i18n-parent');
        $rowParents.hide();
        $rowParents.has('.' + codeLang).show();
    });
    jQuery('.list_lang_title').eq(0).trigger('change');
}

/**
 * Alma admin fee plans helper.
 */
function AlmaAdminFeePlan(helper) {
    /**
     * Decorate / render fee plan select & options.
     *
     * @return void
     */
    var renderFeePlan = function () {
        jQuery.widget('custom.almaSelectMenu', jQuery.ui.selectmenu, {
            _renderButtonItem: function (item) {
                return jQuery('<span>', {
                    text: item.label,
                    id: this.options.id + '_button_' + item.value,
                    class: "ui-selectmenu-text " + getOptionStatusClass(item.value),
                    style: 'border: 1px solid #8c8f94; background: white; border-radius: 4px; line-height: 1; width: 400px; font-size: 14px;'
                });
            },
            _renderItem: function ($ul, item) {
                var $li = jQuery('<li>', {
                    class: "ui-menu-item li_" + item.value
                });
                var $wrapper = jQuery('<div>', {
                    text: item.label,
                    class: 'ui-menu-item-wrapper ' + getOptionStatusClass(item.value),
                    id: this.options.id + '_item_' + item.value
                });
                $li.append($wrapper);
                return $li.appendTo($ul);
            }
        });
    }

    /**
     * Build option classes depending on enabled check / uncheck status.
     *
     * @param {string} optionValue as plan.
     * @return {string}
     */
    var getOptionStatusClass = function (optionValue) {
        return jQuery('#woocommerce_alma_enabled_' + optionValue).is(":checked") ? 'alma_option_enabled' : 'alma_option_disabled';
    }
    /**
     * Loop on injected alma select ids.
     *
     * @return void
     */
    var initiateAlmaSelectMenuBehaviour = function () {
        if (typeof select_alma_fee_plans_id === 'undefined') {
            return;
        }
        var $select = jQuery('#' + select_alma_fee_plans_id);
        var previousPlan = $select.val();
        $select.almaSelectMenu({
            id: select_alma_fee_plans_id,
            /**
             * Display another feePlan on change (only if min max inputs are ok).
             */
            change: function (event) {
                var plan = $select.val();
                var showingPlan = true;
                jQuery('#woocommerce_alma_min_amount_' + previousPlan + ', #woocommerce_alma_max_amount_' + previousPlan).each(function () {
                    var $input = jQuery(this);
                    if (!helper.isBetweenMinMax($input)) {
                        jQuery('button[type=submit]').click();
                        showingPlan = false;
                        event.preventDefault();
                        return false;
                    }
                })
                if (showingPlan) {
                    showPlan(plan);
                    previousPlan = plan;
                }
            }
        });
    }
    /**
     * Listen feePlan checkbox status then toggle select options status.
     *
     * @return void
     */
    var listenFeePlanCheckboxStatus = function () {
        jQuery('[id^=woocommerce_alma_enabled_]').change(function () {
            var $checkbox = jQuery(this);
            var plan = $checkbox.attr('id').substring(25);
            var selector = '#' + select_alma_fee_plans_id + '_item_' + plan + ", #" + select_alma_fee_plans_id + '_button_' + plan;
            helper.toggleItems($checkbox, jQuery(selector));
        });
    }
    /**
     * Handle submit action to check inputs and focus on error if any.
     *
     * @return void
     */
    var checkInputsOnSubmitActionTriggered = function () {
        jQuery(document).on('click', 'form button[type=submit]', function (e) {
            var isValid = true;
            var $input = null;
            jQuery('[id^=woocommerce_alma_min_amount_], [id^=woocommerce_alma_max_amount_]').each(function () {
                $input = jQuery(this);
                isValid &= helper.isBetweenMinMax($input);
                if (!isValid) {
                    return false;
                }
            });
            if (!isValid) {
                var plan = $input.attr('id').substring(28);
                var $select = jQuery('#' + select_alma_fee_plans_id);
                if ($select.find('option[value=' + plan + ']').length) {
                    $select.val(plan);
                    $select.almaSelectMenu('refresh');
                    showPlan(plan);
                    helper.scrollToSection(plan);
                }
                e.preventDefault();
            }
        });
    }
    /**
     * Show plan by planKey with effects.
     *
     * @param {string} plan The selected plan.
     */
    var showPlan = function (plan) {
        jQuery('.alma_fee_plan').stop(true, true).hide();
        var $sections = jQuery('.alma_fee_plan_' + plan);
        $sections.show().effect('highlight', 1500);
        $sections.find('b').effect('highlight', {color: 'pink'}, 5000);
    }

    return {
        renderFeePlan: renderFeePlan,
        getOptionStatusClass: getOptionStatusClass,
        initiateAlmaSelectMenuBehaviour: initiateAlmaSelectMenuBehaviour,
        listenFeePlanCheckboxStatus: listenFeePlanCheckboxStatus,
        checkInputsOnSubmitActionTriggered: checkInputsOnSubmitActionTriggered,
        showPlan: showPlan
    }
}

var collapsible = jQuery(".alma-legal-checkout-collapsible");

if(collapsible.length) {
    var chevron = jQuery('#alma-legal-collapse-chevron');

    collapsible[0].addEventListener("click", function () {
        this.classList.toggle("active");
        var content = this.nextElementSibling;
        if (content.style.display === "block") {
            content.style.display = "none";
            chevron.addClass('bottom');
        } else {
            content.style.display = "block";
            chevron.removeClass('bottom');
        }
    });
}

add_button_show_pwd('woocommerce_alma_live_api_key');
add_button_show_pwd('woocommerce_alma_test_api_key');

function add_button_show_pwd(button_name) {
    var button = jQuery('#' + button_name );
    button.after('<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Show password" onclick="toggle_pwd_field(\''+ button_name + '\')"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button>')

}
function toggle_pwd_field(field) {
    var pass_id = jQuery('input[name="'+field+'"]').attr("id");
    var x = document.getElementById(pass_id);
    if (x.type === "password") {
        x.type = "text";
    } else {
        x.type = "password";
    }
}
