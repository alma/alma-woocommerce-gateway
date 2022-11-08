jQuery(document).ready(function () {

    var coll = jQuery(".legal-checkout-collapsible");
    var chevron = jQuery('#legal-collapse-chevron');
    var i;
    for (i = 0; i < coll.length; i++) {
        coll[i].addEventListener("click", function () {
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


    jQuery(".button-checkout-legal").on("click", function (e) {
        e.preventDefault();
        value =  jQuery(this).data('value');

        var data = {
            'action': 'legal_alma',
            'accept': value
        };

        modalSoc = jQuery('#alma-modal-soc');

        jQuery.post(ajax_object.ajax_url, data, function(response) {
            if( ! response.success) {
                modal = '<div class="notice notice-error is-dismissible">' +
                    '<p>'+ response.data.message+  ' </p>' +
                    '</div>'
                modalSoc.before( modal );
            }

            if(response.success) {
                modal = '<div class="notice notice-info is-dismissible">' +
                    '<p>'+ response.data.message+  ' </p>' +
                    '</div>'
                modalSoc.before( modal );
                modalSoc.remove();
            }
        });
    });

});
