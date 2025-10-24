(function ($, window) {
    'use strict';

    var settings = window.DokanDHLVendorOrders || {};
    var apiFetch = window.wp && window.wp.apiFetch ? window.wp.apiFetch : null;

    function getRestPath(orderId, resource) {
        var basePath = settings.restBasePath || '/dokan-dhl/v1';
        basePath = basePath.replace(/\/$/, '');
        return basePath + '/orders/' + orderId + '/' + resource;
    }

    function getRestUrl(orderId, resource) {
        var root = settings.root || (window.wpApiSettings && window.wpApiSettings.root) || window.location.origin + '/wp-json/';
        root = root.replace(/\/$/, '');
        var path = getRestPath(orderId, resource);
        return root + path;
    }

    function sanitizeNumber(value) {
        if ( value === null || value === undefined ) {
            return '';
        }

        if ( typeof value === 'string' ) {
            value = value.replace(',', '.');
        }

        if ( value === '' ) {
            return '';
        }

        var floatValue = parseFloat(value);

        if ( isNaN(floatValue) ) {
            return '';
        }

        return floatValue;
    }

    function requestApi(options) {
        if ( apiFetch ) {
            var fetchArgs = {
                path: options.path,
                method: options.method || 'GET'
            };

            if ( options.data !== undefined ) {
                fetchArgs.data = options.data;
            }

            if ( settings.nonce ) {
                fetchArgs.headers = fetchArgs.headers || {};
                fetchArgs.headers['X-WP-Nonce'] = settings.nonce;
            }

            return apiFetch(fetchArgs);
        }

        var headers = {
            'Content-Type': 'application/json'
        };

        if ( settings.nonce ) {
            headers['X-WP-Nonce'] = settings.nonce;
        }

        return new Promise(function (resolve, reject) {
            $.ajax({
                url: getRestUrl(options.orderId, options.resource),
                method: options.method,
                data: options.data ? JSON.stringify(options.data) : null,
                contentType: 'application/json',
                dataType: 'json',
                headers: headers
            }).done(resolve).fail(function (jqXHR) {
                reject(jqXHR);
            });
        });
    }

    function showNotice($panel, message, type) {
        var $notice = $panel.find('.dokan-dhl-notice');

        if ( ! $notice.length ) {
            return;
        }

        $notice.removeClass('dokan-alert dokan-alert-success dokan-alert-danger dokan-alert-warning');

        if ( 'success' === type ) {
            $notice.addClass('dokan-alert dokan-alert-success');
        } else {
            $notice.addClass('dokan-alert dokan-alert-danger');
        }

        $notice.text(message).show();
    }

    function hideNotice($panel) {
        var $notice = $panel.find('.dokan-dhl-notice');

        if ( $notice.length ) {
            $notice.hide();
        }
    }

    function updatePanelWithShipment($panel, payload) {
        if ( ! payload ) {
            return;
        }

        var awb = payload.awb || '';
        var downloadUrl = payload.download_url || '';
        var trackingStatus = payload.tracking_status || '';
        var trackingEvents = payload.tracking_events || [];

        $panel.attr('data-has-label', '1');
        $panel.find('.dokan-dhl-awb-value').text(awb || settings.i18n.awbPending || 'Pending');
        $panel.find('.dokan-dhl-create-section').hide();
        $panel.find('.dokan-dhl-existing-section').show();

        var $downloadButton = $panel.find('.dokan-dhl-download-label');

        if ( $downloadButton.length ) {
            if ( downloadUrl ) {
                $downloadButton.prop('disabled', false);
            } else {
                $downloadButton.prop('disabled', true);
            }

            $downloadButton.attr('data-download-url', downloadUrl);
        }

        var $trackingStatus = $panel.find('.dokan-dhl-tracking-status-value');

        if ( $trackingStatus.length ) {
            $trackingStatus.text(trackingStatus || settings.i18n.trackingUnknown || '—');
        }

        var $empty = $panel.find('.dokan-dhl-tracking-empty');
        var $eventsList = $panel.find('.dokan-dhl-tracking-events');

        if ( $eventsList.length ) {
            $eventsList.empty();

            if ( trackingEvents && trackingEvents.length ) {
                trackingEvents.forEach(function (event) {
                    var description = event.description || '';
                    var location = event.location || '';
                    var timestamp = event.timestamp || '';
                    var $item = $('<li />');

                    if ( description ) {
                        $('<span />').addClass('dokan-dhl-tracking-event-description').text(description).appendTo($item);
                    }

                    if ( location ) {
                        $('<span />').addClass('dokan-dhl-tracking-event-location').text(' — ' + location).appendTo($item);
                    }

                    if ( timestamp ) {
                        $('<span />').addClass('dokan-dhl-tracking-event-timestamp').text(' (' + timestamp + ')').appendTo($item);
                    }

                    $eventsList.append($item);
                });

                $eventsList.show();

                if ( $empty.length ) {
                    $empty.hide();
                }
            } else {
                $eventsList.hide();

                if ( $empty.length ) {
                    $empty.show();
                }
            }
        }

        if ( payload.package ) {
            if ( payload.package.weight !== undefined ) {
                $panel.find('.dokan-dhl-field-weight').val(payload.package.weight);
            }

            if ( payload.package.length !== undefined ) {
                $panel.find('.dokan-dhl-field-length').val(payload.package.length);
            }

            if ( payload.package.width !== undefined ) {
                $panel.find('.dokan-dhl-field-width').val(payload.package.width);
            }

            if ( payload.package.height !== undefined ) {
                $panel.find('.dokan-dhl-field-height').val(payload.package.height);
            }
        }

        if ( payload.rest_nonce ) {
            $panel.attr('data-rest-nonce', payload.rest_nonce);
        }
    }

    $(document).on('click', '.dokan-dhl-create-label', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $panel = $button.closest('.dokan-dhl-order-panel');
        var orderId = $panel.data('order-id');

        if ( ! orderId ) {
            return;
        }

        hideNotice($panel);
        $button.prop('disabled', true);

        var payload = {
            weight: sanitizeNumber($panel.find('.dokan-dhl-field-weight').val()),
            length: sanitizeNumber($panel.find('.dokan-dhl-field-length').val()),
            width: sanitizeNumber($panel.find('.dokan-dhl-field-width').val()),
            height: sanitizeNumber($panel.find('.dokan-dhl-field-height').val())
        };

        var requestOptions = {
            path: getRestPath(orderId, 'label'),
            method: 'POST',
            data: payload
        };

        requestOptions.orderId = orderId;
        requestOptions.resource = 'label';

        requestApi(requestOptions)
            .then(function (response) {
                updatePanelWithShipment($panel, response);

                if ( response && response.rest_nonce ) {
                    settings.nonce = response.rest_nonce;
                }

                showNotice($panel, settings.i18n.labelCreated || 'Label created.', 'success');
            })
            .catch(function (error) {
                var message = settings.i18n.labelCreateError || 'Unable to create the DHL label.';

                if ( error ) {
                    if ( error.message ) {
                        message = error.message;
                    } else if ( error.responseJSON && error.responseJSON.message ) {
                        message = error.responseJSON.message;
                    }
                }

                showNotice($panel, message, 'error');
            })
            .finally(function () {
                $button.prop('disabled', false);
            });
    });

    $(document).on('click', '.dokan-dhl-download-label', function (event) {
        event.preventDefault();

        var $button = $(this);

        if ( $button.is(':disabled') ) {
            return;
        }

        var url = $button.data('download-url');

        if ( ! url ) {
            var $panel = $button.closest('.dokan-dhl-order-panel');
            var orderId = $panel.data('order-id');
            var nonce = $panel.attr('data-rest-nonce') || settings.nonce || '';

            if ( orderId ) {
                url = getRestUrl(orderId, 'label');

                if ( nonce ) {
                    url += (url.indexOf('?') === -1 ? '?' : '&') + '_wpnonce=' + encodeURIComponent(nonce);
                }
            }
        }

        if ( url ) {
            window.open(url, '_blank');
        }
    });

    $(document).on('click', '.dokan-dhl-refresh-tracking', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $panel = $button.closest('.dokan-dhl-order-panel');
        var orderId = $panel.data('order-id');

        if ( ! orderId ) {
            return;
        }

        hideNotice($panel);
        $button.prop('disabled', true);

        var requestOptions = {
            path: getRestPath(orderId, 'tracking'),
            method: 'GET'
        };

        requestOptions.orderId = orderId;
        requestOptions.resource = 'tracking';

        requestApi(requestOptions)
            .then(function (response) {
                updatePanelWithShipment($panel, response);

                if ( response && response.rest_nonce ) {
                    settings.nonce = response.rest_nonce;
                }

                showNotice($panel, settings.i18n.trackingUpdated || 'Tracking updated.', 'success');
            })
            .catch(function (error) {
                var message = settings.i18n.trackingError || 'Unable to refresh tracking at this time.';

                if ( error ) {
                    if ( error.message ) {
                        message = error.message;
                    } else if ( error.responseJSON && error.responseJSON.message ) {
                        message = error.responseJSON.message;
                    }
                }

                showNotice($panel, message, 'error');
            })
            .finally(function () {
                $button.prop('disabled', false);
            });
    });
})(jQuery, window);
