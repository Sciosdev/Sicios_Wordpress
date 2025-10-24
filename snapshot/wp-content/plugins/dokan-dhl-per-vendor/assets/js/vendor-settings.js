(function ($, window) {
    'use strict';

    var settings = window.DokanDHLVendorSettings || {};

    function getAjaxUrl() {
        if ( settings.ajaxUrl ) {
            return settings.ajaxUrl;
        }

        if ( window.ajaxurl ) {
            return window.ajaxurl;
        }

        return '';
    }

    function getMessage(key, fallback) {
        if ( settings.i18n && settings.i18n[key] ) {
            return settings.i18n[key];
        }

        return fallback || '';
    }

    function showNotice(type, message) {
        var normalizedType = 'success' === type ? 'success' : 'error';
        var helpers = window.dokan || {};

        if ( helpers.notice && 'function' === typeof helpers.notice ) {
            helpers.notice(message, { type: normalizedType });
            return;
        }

        if (
            helpers.utils &&
            helpers.utils.Toast &&
            'function' === typeof helpers.utils.Toast[ normalizedType ]
        ) {
            helpers.utils.Toast[ normalizedType ](message);
            return;
        }

        if (
            helpers.dashboard &&
            helpers.dashboard.Notice &&
            'function' === typeof helpers.dashboard.Notice[ normalizedType ]
        ) {
            helpers.dashboard.Notice[ normalizedType ](message);
            return;
        }

        var $container = $('.dokan-dhl-test-notice');

        if ( ! $container.length ) {
            $container = $('<div />')
                .addClass('dokan-dhl-test-notice dokan-alert')
                .attr('role', 'status')
                .attr('aria-live', 'polite')
                .appendTo('.dokan-dhl-settings');
        }

        $container
            .removeAttr('hidden')
            .removeClass('dokan-alert-success dokan-alert-danger dokan-alert-error')
            .addClass('success' === normalizedType ? 'dokan-alert-success' : 'dokan-alert-danger')
            .text(message)
            .show();
    }

    function restoreButton($button, label) {
        $button.prop('disabled', false);

        if ( label ) {
            $button.text(label);
        }

        $button.removeData('busy');
    }

    $(document).on('click', '.dokan-dhl-test-connection', function (event) {
        event.preventDefault();

        var $button = $(this);

        if ( $button.data('busy') ) {
            return;
        }

        var ajaxUrl = getAjaxUrl();

        if ( ! ajaxUrl ) {
            showNotice('error', getMessage('unexpected', '')); 
            return;
        }

        var nonce = $button.data('nonce') || settings.nonce || '';

        if ( ! nonce ) {
            showNotice('error', getMessage('nonceError', ''));
            return;
        }

        var vendorId = $button.data('vendorId') || '';
        var defaultText = $button.data('defaultText');

        if ( typeof defaultText === 'undefined' || defaultText === '' ) {
            defaultText = $.trim($button.text());
            $button.data('defaultText', defaultText);
        }

        var testingText = getMessage('testing', defaultText);

        $button.data('busy', true);
        $button.prop('disabled', true);

        if ( testingText ) {
            $button.text(testingText);
        }

        $.ajax({
            url: ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'dokan_dhl_test_connection',
                nonce: nonce,
                vendor_id: vendorId
            }
        })
            .done(function (response) {
                if ( response && response.success ) {
                    var successMessage = (response.data && response.data.message) ? response.data.message : getMessage('success', '');
                    showNotice('success', successMessage);
                    return;
                }

                var errorMessage = getMessage('error', '');

                if ( response && response.data && response.data.message ) {
                    errorMessage = response.data.message;
                }

                showNotice('error', errorMessage);
            })
            .fail(function (jqXHR) {
                var message = getMessage('unexpected', '');

                if (
                    jqXHR &&
                    jqXHR.responseJSON &&
                    jqXHR.responseJSON.data &&
                    jqXHR.responseJSON.data.message
                ) {
                    message = jqXHR.responseJSON.data.message;
                }

                showNotice('error', message);
            })
            .always(function () {
                restoreButton($button, defaultText);
            });
    });
})(jQuery, window);
