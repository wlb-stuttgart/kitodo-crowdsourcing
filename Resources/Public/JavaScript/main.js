
$( document ).ready(function() {

    FontAwesomeConfig = { autoReplaceSvg: false }

    clickEvents();

    openLayer();

    datePicker();

});

function datePicker() {
    $('.datepicker').datetimepicker({
        timepicker:false,
        format:'d.m.Y',
        yearStart: 500,
        closeOnDateSelect: false,
        closeOnWithoutClick: true,
        onSelectDate:function(ct,input){
            this.setOptions({format: 'd.m.Y'});
            input.currentValue = ('0' + (ct.getDay()+1)).slice(-2) + '.' + ('0' + (ct.getMonth()+1)).slice(-2) + '.' + ct.getFullYear();
        },
        onChangeMonth: function (ct, input) {
            this.setOptions({format: 'm.Y'});
            input.currentValue = ('0' + (ct.getMonth()+1)).slice(-2) + '.' + ct.getFullYear();
            input.val(input.currentValue);
        },
        onChangeYear: function (ct, input) {
            this.setOptions({format: 'Y'});
            input.currentValue = ct.getFullYear();
            input.val(input.currentValue);
        },
        onClose: function (ct, input) {
            if (input.currentValue !== undefined) {
                input.val(input.currentValue);
            }
        }
    });

}

function openLayer() {
    const extent = [0, 0, 1024, 968];
    const projection = new ol.proj.Projection({
        code: 'image',
        units: 'pixels',
        extent: extent,
    });
    var source = [];
    $('.processimages').each(function () {
        var tempSource = new ol.source.ImageStatic({
            attributions: '',
            url: $(this).data('path'),
            projection: projection,
            imageExtent: [0, 0, $(this).data('width'), $(this).data('height')],
        });
        source.push(tempSource);
    });

    const imageLayer = new ol.layer.Image({
        source: source[0],
    });

    const map = new ol.Map({
        layers: [
            imageLayer,
        ],
        target: 'map',
        view: new ol.View({
            projection: projection,
            center: [1000,1000],
            zoom: 0,
            maxZoom: 6,
        }),
    });

    $('.ol-prev-image').on('click', function (evt) {
        var currentSource = $('#map').data('sourcecount');
        if (currentSource > 0) {
            $('#map').data('sourcecount', currentSource-1);
            imageLayer.setSource(source[currentSource-1]);
        }
    });

    $('.ol-next-image').on('click', function (evt) {
        var currentSource = $('#map').data('sourcecount');
        var maxCount = $('.processimages').length;
        if (currentSource < maxCount-1) {
            $('#map').data('sourcecount', currentSource+1);
            imageLayer.setSource(source[currentSource+1]);
        }
    });
}

function clickEvents() {

    // Hide all empty input fields which are not required
    $('input.processForm:not([required]):not([data-required="1"])').each(function () {
        if ($(this).val() === '') {
            $(this).parent().hide();
        }
    });

    // Hide all metadata groups with empty input fields
    $('div.group-with-children .child-group').each(function() {
        let allInputs = $(this).find('div[data-required="0"] input');
        let hasValue = allInputs.filter(function() {
            return $(this).val().trim() !== "";
        }).length > 0;

        if (!hasValue) {
            $(this).hide();
        }
    });


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

        // find elements which are required
        var requiredFields = configElement.find('input[data-required="1"]');
        requiredFields.attr('required', 'true');

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

        var requiredFields = metadataGroup.find('input[required]');
        requiredFields.removeAttr('required');
    });

}