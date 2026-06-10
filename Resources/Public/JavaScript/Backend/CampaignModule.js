// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

jQuery( document ).ready(function() {

    jQuery('#cancelEdit').on('click', function (evt) {
        window.history.back();
    });

    jQuery('#cancelNew').on('click', function (evt) {
        window.history.back();
    });
});
