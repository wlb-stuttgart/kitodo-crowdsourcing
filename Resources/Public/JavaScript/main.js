// Landing page
var navbarHeight = 60;
var isScrolling = false;
var $navbarCollapse = $('#navbarNav');
var scrollTimer;
var map = null;

$( document ).ready(function() {

    FontAwesomeConfig = {autoReplaceSvg: false}

    clickEvents();

    if ($('#map').length > 0) {
        openLayer();
    }

    datePicker();

    generateSectionLinks();

    scrollButtons();

    tabNavigation();
    
    refreshTabNavigationButtons();

    loginAndRegisterModals();

    initModalKeyboardSupport();

    initDashbord();

});

function validateTabs() {
    $('#nav-tabContent .tab-pane').each(function () {
        const tabId = $(this).attr('id');
        const requiredFields = $('#' + tabId).find('*[required]');
        let hasError = false;

        requiredFields.each(function () {
            if (!$(this).val()) {
                hasError = true;
                $(this).addClass('form-error')
                return false; // break loop
            }
        });

        if (hasError) {
            $('button#' + tabId + '-tab').addClass('tab-error');
        } else {
            $('button#' + tabId + '-tab').removeClass('tab-error');
        }
    });
}

function scrollButtons() {
    $(window).on('scroll resize load', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(checkScrollArrows, 100);
    });

    $('.scroll-arrow-up').on('click', scrollToPreviousSection);
    $('.scroll-arrow-down').on('click', scrollToNextSection);

    $('#navbarNav .nav-link').on('click', function () {
        if (isMobile()) {
            $('.navbar-toggler').trigger('click');  // Collapse the mobile menu
        }
    });
}

function tabNavigation() {
    const $tabs = $('#nav-tab button');
    let currentIndex = $tabs.index($tabs.filter('.active'));

    function activateTab(index) {
        if (index >= 0 && index < $tabs.length) {
            $tabs.eq(index).tab('show');
            currentIndex = index;
        }
    }

    $('#nextBtn').on('click', function (evt) {
        evt.preventDefault();
        if (currentIndex < $tabs.length - 1) {
            activateTab(currentIndex + 1);
        }
    });

    $('#prevBtn').on('click', function (evt) {
        evt.preventDefault();
        if (currentIndex > 0) {
            activateTab(currentIndex - 1);
        }
    });

    // Aktuellen Index auch beim manuellen Tab-Wechsel aktualisieren
    $tabs.on('shown.bs.tab', function (e) {
        currentIndex = $tabs.index($(e.target));
    });
}

function toggleTabNavigationButtons() {
    const $tabs = $('#nav-tab button');
    let currentIndex = $tabs.index($tabs.filter('.active'));

    if (currentIndex > $tabs.length - 2) {
        $('#nextBtn').hide();
    }

    if (currentIndex > 0 && currentIndex < $tabs.length - 1) {
        $('#nextBtn').show();
    }

    if (currentIndex > 0) {
        $('#prevBtn').show();
    }

    if (currentIndex == 0) {
        $('#prevBtn').hide();
    }
}

function refreshTabNavigationButtons() {
    toggleTabNavigationButtons();
    $('#nav-tab .nav-link').on('shown.bs.tab', function (e) {
        e.preventDefault();
        toggleTabNavigationButtons();
    });
}

function datePicker() {
    $('.datepicker').datetimepicker({
        timepicker: false,
        format: 'd.m.Y',
        yearStart: 500,
        closeOnDateSelect: false,
        closeOnWithoutClick: true,
        onSelectDate: function (ct, input) {
            this.setOptions({format: 'd.m.Y'});
            input.currentValue = ('0' + (ct.getDay() + 1)).slice(-2) + '.' + ('0' + (ct.getMonth() + 1)).slice(-2) + '.' + ct.getFullYear();
        },
        onChangeMonth: function (ct, input) {
            this.setOptions({format: 'm.Y'});
            input.currentValue = ('0' + (ct.getMonth() + 1)).slice(-2) + '.' + ct.getFullYear();
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

    const initialWidth = $('.processimages').first().data('width');
    const initialHeight = $('.processimages').first().data('height');
    const extent = [0, 0, initialWidth, initialHeight];

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
            imageExtent: [0, 0, $(this).data('width'), $(this).data('height')]
        });
        source.push(tempSource);
    });

    const imageLayer = new ol.layer.Image({
        source: source[0],
    });
    
    map = new ol.Map({
        layers: [
            imageLayer,
        ],
        target: 'map',
        view: new ol.View({
            projection: projection,
            center: [initialWidth/2, initialHeight/2],
            zoom: 1,
            maxZoom: 6,
        }),
        controls: [],
    });

    map.getView().fit(imageLayer.getSource().getImageExtent(), {
        size: map.getSize(),
        padding: [0,0,0,0],
        nearest: true,
        duration: 0
    });

    $('.ol-prev-image').on('click', function (evt) {
        var currentSource = $('#map').data('sourcecount');
        if (currentSource > 0) {
            $('#map').data('sourcecount', currentSource-1);
            imageLayer.setSource(source[currentSource-1]);
            map.getView().fit(source[currentSource-1].getImageExtent(), {
                size: map.getSize(),
                padding: [0,0,0,0],
                nearest: true,
                duration: 0
            });
        }
    });

    $('.ol-next-image').on('click', function (evt) {
        var currentSource = $('#map').data('sourcecount');
        var maxCount = $('.processimages').length;
        if (currentSource < maxCount-1) {
            $('#map').data('sourcecount', currentSource+1);
            imageLayer.setSource(source[currentSource+1]);
            map.getView().fit(source[currentSource+1].getImageExtent(), {
                size: map.getSize(),
                padding: [0,0,0,0],
                nearest: true,
                duration: 0
            });
        }
    });
}

function clickEvents() {

    // Hide all empty input fields which are not required
    $('input.processForm:not([required]):not([data-required="1"])').each(function () {
        if ($(this).val() === '' && $(this).data('max') !== 1) {
            $(this).parent().hide();
        }
    });

    $('textarea.processForm:not([required]):not([data-required="1"])').each(function () {
        if ($(this).val() === '' && $(this).data('max') !== 1) {
            $(this).parent().hide();
        }
    });

    $('select.processForm:not([required]):not([data-required="1"])').each(function () {
        if ($(this).val() === '' && $(this).data('max') !== 1) {
            $(this).parent().hide();
        }
    });

    // Hide all metadata groups with empty input fields
    $('div.group-with-children .child-group').each(function() {
        let groupRequired = $(this).find('div.card-body[data-required="1"]');
        let allInputs = $(this).find('div[data-required="0"] input');
        let hasValue = allInputs.filter(function() {
            return $(this).val().trim() !== "";
        }).length > 0;

        if (!hasValue && groupRequired.length === 0) {
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

        // Duplicate code is needed
        var nextHidden = $(configElement).find('div:hidden').first();
        // Hide the next button if there is no next hidden field
        if (nextHidden.next().length === 0) {
            $(this).hide();
        }

    });

    // Click event for adding new group
    $('button.addGroup').on('click', function (evt) {
        // get next hidden field and show it
        evt.preventDefault();

        var configElement = $(this).closest('.metadata-group');
        var nextHidden = $(configElement).find('div.metadata-group:hidden').first();
        nextHidden.show();

        // find elements which are required
        var requiredFields = nextHidden.find('input[data-required="1"]');
        requiredFields.attr('required', 'true');

        nextHidden = $(configElement).find('div.metadata-group:hidden').first();
        // Hide the next button if there is no next hidden field
        if (nextHidden.length === 0) {
            $(this).hide();
        }

    });

    // Click event for hiding form field and empty this field
    $('.deleteChildField').on('click', function (evt) {
        $(this).closest('.input-group').hide();
        $(this).siblings('input').val('');
        $(this).closest('.child-metadata-field').find('button.addFormGroupField').show();
    });

    // Click event for hiding form field and empty this field
    $('.deleteField').on('click', function (evt) {
        $(this).closest('.input-group').hide();
        $(this).siblings('input').val('');
        $(this).closest('.metadata-group').find('button.addFormField').show();
    });

    // Click event for adding new groups
    $('button.addFormGroupField').on('click', function (evt) {
        // get next hidden field and show it
        evt.preventDefault();

        var nextHidden = $(this).closest('.child-metadata-field').find('.metadataChildField div.input-group:hidden').first();
        nextHidden.show();

        // Duplicate code is needed
        nextHidden = $(this).closest('.child-metadata-field').find('.metadataChildField div.input-group:hidden').first();
        // Hide the next button if there is no next hidden field
        if (nextHidden.length === 0) {
            $(this).hide();
        }
    });

    // Click event for hiding form groups
    $('.deleteGroup').on('click', function (evt) {
        var metadataGroup = $(this).closest('.metadata-group').hide();
        // find all input fields and try to clear them
        $(metadataGroup).find(':input').val('');

        var requiredFields = metadataGroup.find('input[required]');
        requiredFields.removeAttr('required');

        $(this).closest('.metadata-group.form-group').find('button.addGroup').show();
    });

    // Validate form
    $('form.processForm').on('click', function (evt) {
        validateTabs();
    });

    // Remove required on abort / No validation needed
    $('input.abortEdit').on('click', function (evt) {
        $('form.processForm [required]').removeAttr('required');
    });


    // image rotation
    $('#rotate-left').on('click', function (evt) {
        var view = map.getView();
        // Get the current rotation
        var currentRotation = view.getRotation();

        // Rotate by 90 degrees
        var newRotation = currentRotation - Math.PI / 2;

        // Set the new rotation
        // view.setRotation(newRotation);
        view.animate({ rotation: newRotation, duration: 250});
    });

    $('#rotate-right').on('click', function (evt) {
        var view = map.getView();
        // Get the current rotation
        var currentRotation =view.getRotation();

        // Rotate by 90 degrees
        var newRotation = currentRotation + Math.PI / 2;

        // Set the new rotation
        //view.setRotation(newRotation);
        view.animate({ rotation: newRotation, duration: 250});
    });

    //zoom
    $('#zoom-in').on('click', function (evt) {
        var view = map.getView();
        var zoom = view.getZoom();
        view.animate({ zoom: zoom + 1, duration: 250});
    });

    $('#zoom-out').on('click', function (evt) {
        var view = map.getView();
        var zoom = view.getZoom();
        view.animate({ zoom: zoom - 1, duration: 250});
    });

    $('#show-thumbnails').on('click', function (evt) {
        $('#thumbnails').toggle();
        $('#map').toggle();
    });

    $('#fullscreen').on('click', function (evt) {
        var elem = $('#map')[0];
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        }
    });

}

function isMobile() {
    return $(window).width() <= 768;
}

function getScrollOffset() {
    return isMobile() ? navbarHeight : 0;
}

function scrollToNextSection() {
    if (isScrolling) return;
    isScrolling = true;

    var currentScroll = $(window).scrollTop();
    $('.scroll-section').each(function () {
        var sectionTop = $(this).offset().top;
        if (sectionTop > currentScroll + getScrollOffset()) {
            $('html, body').stop().animate({
                scrollTop: sectionTop - getScrollOffset()
            }, 600, function () {
                isScrolling = false;
            });
            collapseNavbar();
            return false;
        }
    });
}

function scrollToPreviousSection() {
    if (isScrolling) return;
    isScrolling = true;

    var currentScroll = $(window).scrollTop();
    var sections = $('.scroll-section').get().reverse();

    $.each(sections, function (i, el) {
        var sectionTop = $(el).offset().top;
        if (sectionTop < currentScroll - 10) {
            $('html, body').stop().animate({
                scrollTop: sectionTop - getScrollOffset()
            }, 600, function () {
                isScrolling = false;
            });
            collapseNavbar();
            return false;
        }
    });
}

function collapseNavbar() {
    if ($navbarCollapse.hasClass('in') || $navbarCollapse.hasClass('show')) {
        $('.navbar-toggler').trigger('click');
    }
}

function checkScrollArrows() {
    var scrollTop = $(window).scrollTop();
    var windowHeight = $(window).height();
    var docHeight = $(document).height();

    $('.scroll-arrow-up').toggle(scrollTop > 0);
    $('.scroll-arrow-down').toggle(scrollTop + windowHeight < docHeight - 1);
}

function generateSectionLinks() {
    $('h2[id^="section-"]').each(function() {
        const text = $(this).text();
        const id = $(this).attr('id');
        $('ul.navbar-nav').append(`<li class="nav-item"><a class="nav-link" href="#${id}">${text}</a></li>`);
        $('.nav-links').append(`<a href="#${id}">${text}</a>`);
    });
}

function loginAndRegisterModals() {

    // Check if there is an action uri in local storage and redirect to it.
    // This is used for the login and the registration modal dialog.
    var actionUri = localStorage.getItem("action-uri");
    var nextActionUri = localStorage.getItem("next-action-uri");

    if (typeof actionUri !== undefined && actionUri !== null && actionUri !== "") {
        if (isLoginProcessActive()) {
            if (jQuery('#loginModal')) {
                var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            }
        } else if (isRegistrationProcessActive()) {
            var registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
            setRegistrationProcessActive();
            registerModal.show();
        } else {
            if (nextActionUri !== undefined && nextActionUri !== null && nextActionUri !== "") {
                actionUri = nextActionUri;
            }
            localStorage.removeItem("next-action-uri");
            localStorage.removeItem("action-uri");
            window.location.href = actionUri;
        }
    } else {
        if (isLoginProcessActive()) {
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        } else {
            if (jQuery("#registerConfirmModal").length > 0) {
                var registerConfirmModal = new bootstrap.Modal(document.getElementById('registerConfirmModal'));
                if (registerConfirmModal !== undefined && registerConfirmModal !== null) {
                    registerConfirmModal.show();
                }
            }
        }
    }

    jQuery('.login, .loginRequired').on('click', function(e){
        if (jQuery('#loginModal')) {
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));

            // Store the action uri in local storage.
            // This is used for the redirect after login modal.
            if (jQuery(this).hasClass('loginRequired')) {
                localStorage.setItem("next-action-uri", jQuery(this).attr('href'));
            }

            var activePage = jQuery('.main-nav-links a.active').first();
            if (typeof activePage !== 'undefined' && activePage) {
                localStorage.setItem("action-uri", activePage.attr('href'));
            }

            loginModal.show();
            e.preventDefault();
        }
    });

    jQuery('#loginModal').on('hidden.bs.modal', function () {
        localStorage.removeItem("next-action-uri");
        redirectToActivePage();
    });

    jQuery('#loginModal .btn-close').on('click', function () {
        localStorage.removeItem("next-action-uri");
        redirectToActivePage();
    });

    jQuery('#passwordRecoveryLink').on('click', function(e){
      // localStorage.setItem("action-uri", jQuery(this).attr('href'));
    });



    if (jQuery('#registerModal')) {

        jQuery('.register').on('click', function(e){
            var registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
            registerModal.show();
            setRegistrationProcessInactive();
            saveActivePageUri();
            e.preventDefault();
        });

        jQuery("#register-link").on("click", function(e) {
            var registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
            registerModal.show();
            setRegistrationProcessActive();
            saveActivePageUri();
            e.preventDefault();
        })

        jQuery('#registerModal').on('hidden.bs.modal', function () {
            setRegistrationProcessInactive();
            redirectToActivePage();
        });

        jQuery('#registerModal .btn-close').on('click', function () {
            setRegistrationProcessInactive();
            redirectToActivePage();
        });

    }

    if (jQuery('#registerConfirmModal')) {
        jQuery('#registerConfirmModal .btn-close').on('click', function () {
            redirectToActivePage();
        });

        jQuery('#registerConfirmModal').on('hidden.bs.modal', function () {
            redirectToActivePage();
        });
    }

    // Listen for any modal being shown
    document.addEventListener('show.bs.modal', function (event) {
        // Get all currently open modals
        const openModals = document.querySelectorAll('.modal.show');

        // Loop through them and hide, unless it's the one being shown
        openModals.forEach(function (modal) {
            if (modal !== event.target) {
                bootstrap.Modal.getInstance(modal)?.hide();
            }
        });
    });
}

function redirectToActivePage()
{
    var activePage = jQuery('.main-nav-links a.active').first();
    if (typeof activePage !== 'undefined' && activePage) {
        var actionUri = activePage.attr('href');
        window.location.href = actionUri;
    } else {
        window.location.href = "/";
    }
}

function saveActivePageUri() {
    // Store the action uri in local storage.
    // This is used for the redirect after login modal.
    var activePage = jQuery('.main-nav-links a.active').first();
    if (typeof activePage !== 'undefined' && activePage) {
        localStorage.setItem("action-uri", activePage.attr('href'));
    }
}

function setRegistrationProcessActive() {
    localStorage.setItem("registrationProcess", "true");
}

function setRegistrationProcessInactive() {
    localStorage.removeItem("registrationProcess");
}

function isRegistrationProcessActive() {
    return (localStorage.getItem("registrationProcess") === "true") ||
        (typeof registrationProcessActive !== 'undefined' && registrationProcessActive) ||
        (jQuery("#registerModal").find('.error').length > 0);
}

function isLoginProcessActive() {
    return (typeof showLoginDialog !== 'undefined' && showLoginDialog) ||
        (jQuery(".typo3-messages").length > 0);
}

function initModalKeyboardSupport()
{

    $(document).on('keydown', '[data-bs-toggle="modal"][tabindex]', function(e) {
        const $trigger = $(this);

        switch(e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();

                // open modal
                const targetSelector = $trigger.data('bs-target');
                if (targetSelector) {
                    const targetModal = document.querySelector(targetSelector);
                    if (targetModal) {
                        const modal = bootstrap.Modal.getOrCreateInstance(targetModal);
                        modal.show();
                    }
                }
                break;

            case 'Escape':
                // remove focus
                $trigger.blur();
                break;
        }
    });

}

function onModalCloseByEsc(modalId, escCallback, otherCallback) {
    let escPressed = false;
    const $modal = $('#' + modalId);

    $modal.on('keydown', e => { escPressed = e.key === 'Escape'; });
    $modal.on('hide.bs.modal', () => {
        if (escPressed) escCallback && escCallback();
        else otherCallback && otherCallback();
        escPressed = false;
    });
}

function initDashbord() {

    $('#dashboardInfoToggle').on('click', function() {

        setTimeout(() => {
            const expanded = $(this).attr('aria-expanded') === 'true';
            $('#dashboardInfoToggle .arrowDown').toggle(!expanded);
            $('#dashboardInfoToggle .arrowUp').toggle(expanded);
        }, 10);
    });


}



