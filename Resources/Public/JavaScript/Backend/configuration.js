
$( document ).ready(function() {
    clickEvents();
});

function clickEvents() {

    // Hide all empty input fields which are not required
    $('input.processForm:not([required])').parent().hide();

    // Hide all from groups
    $('div.metadata-group div[data-required="0"]').parent().hide();

    // Click event for adding new form fields
    $('button.addFormField').on('click', function (evt) {
        // get next hidden field and show it
        evt.preventDefault();

        var configElement = $(this).closest('.metadata-group');
        var nextHidden = $(configElement).find('div:hidden').first();
        nextHidden.show();
    });

    // Click event for adding new group
    $('button.addGroup').on('click', function (evt) {
        // get next hidden field and show it
        evt.preventDefault();

        var configElement = $(this).closest('.metadata-group');
        var nextHidden = $(configElement).find('div.metadata-group:hidden').first();
        nextHidden.show();
    });

    // Click event for hiding form field and empty this field
    $('.deleteField').on('click', function (evt) {
        $(this).closest('.input-group').hide();
        $(this).siblings('input').val('');
    });

    // Click event for adding new groups
    $('button.addFormGroupField').on('click', function (evt) {
        // get next hidden field and show it
        evt.preventDefault();

        var configElement = $(this).parent().prev('.metadataChildField');
        var nextHidden = $(configElement).find('div:hidden').first();
        nextHidden.show();
    });

    // Click event for hiding form groups
    $('.deleteGroup').on('click', function (evt) {
        var metadataGroup = $(this).closest('.metadata-group').hide();
        // find all input fields and try to clear them
        $(metadataGroup).find(':input').val('');
    });

}