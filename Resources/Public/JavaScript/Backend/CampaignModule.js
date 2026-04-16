
jQuery( document ).ready(function() {

    jQuery('#cancelEdit').on('click', function (evt) {
        window.history.back();
    });

    jQuery('#cancelNew').on('click', function (evt) {
        window.history.back();
    });
});
