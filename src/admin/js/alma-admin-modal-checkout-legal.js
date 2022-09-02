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
});
