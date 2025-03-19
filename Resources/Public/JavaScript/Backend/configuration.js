
$( document ).ready(function() {
    clickEvents();
});

function clickEvents() {

    // Click event for adding new form fields
    $('button.addFormField').on('click', function (evt) {
        evt.preventDefault();
        var configElement = $(this).closest('.form-group');

        var elementId = configElement.attr('id');

        var inputtype = configElement.data('formconfig')['inputtype'];
        var maxOccurs = configElement.data('formconfig')['maxOccurs'];
        var minOccurs = configElement.data('formconfig')['minOccurs'];

        if($('input[name*="metadata[' + elementId + ']"]').length < maxOccurs) {

            var inputGroup = $('<div class="input-group pb-2"><input class="form-control" type="' + inputtype + '" name="metadata[' + elementId + '][]">' +
                '</div>').insertBefore($(this));

            var deleteButton = $('<div class="input-group-text deleteField" style="cursor: pointer;"><span class="fa-solid fa-trash"></div>');

            // click event delete field
            $(deleteButton).on('click', function (evt) {
                $(this).closest('.input-group').remove();
            });

            $(inputGroup).append(deleteButton);
        }

    });

}