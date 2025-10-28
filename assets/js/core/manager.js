/**
 * WP Flyout Manager - Uses WPFlyoutAlert component
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // Handle trigger clicks
        $(document).on('click', '.wp-flyout-trigger', function (e) {
            e.preventDefault();

            const $btn = $(this);
            const handler = $btn.data('flyout-handler');
            const manager = $btn.data('flyout-manager');
            const nonce = $btn.data('flyout-nonce');

            // Collect data attributes
            const data = {};
            $.each(this.dataset, function (key, value) {
                if (key !== 'flyoutHandler' && key !== 'flyoutManager' && key !== 'flyoutNonce') {
                    data[key] = value;
                }
            });

            // Load flyout
            $.post(ajaxurl, {
                action: 'wp_flyout_' + manager,
                handler: handler,
                handler_action: 'load',
                nonce: nonce,
                ...data
            })
                .done(function (response) {
                    if (response.success) {
                        // Remove existing flyouts
                        $('.wp-flyout').remove();

                        // Add new flyout
                        $('body').append(response.data.html);

                        // Get flyout element
                        const $flyout = $('.wp-flyout').last();
                        const flyoutId = $flyout.attr('id');

                        // Open it
                        WPFlyout.open(flyoutId);

                        // Store handler info
                        $flyout.data('handler', handler);
                        $flyout.data('manager', manager);
                        $flyout.data('nonce', nonce);
                        $flyout.data('request-data', data);

                        // Bind save button
                        $flyout.on('click', '.wp-flyout-save', function (e) {
                            e.preventDefault();

                            const $saveBtn = $(this);
                            const originalText = $saveBtn.text();

                            // Show saving state
                            $saveBtn.prop('disabled', true).text('Saving...');

                            // Get form data
                            const $form = $flyout.find('form').first();
                            let formData = '';
                            if ($form.length) {
                                formData = $form.serialize();
                            }

                            // Save via AJAX
                            $.post(ajaxurl, {
                                action: 'wp_flyout_' + manager,
                                handler: handler,
                                handler_action: 'save',
                                nonce: nonce,
                                form_data: formData,
                                ...data
                            })
                                .done(function (saveResponse) {
                                    $saveBtn.prop('disabled', false).text(originalText);

                                    if (saveResponse.success) {
                                        const responseData = saveResponse.data || {};

                                        // Use WPFlyoutAlert component
                                        if (window.WPFlyoutAlert) {
                                            WPFlyoutAlert.show(
                                                responseData.message || 'Saved successfully!',
                                                'success',
                                                {
                                                    target: $flyout.find('.wp-flyout-body'),
                                                    prepend: true,
                                                    timeout: 3000,
                                                    dismissible: true
                                                }
                                            );
                                        }

                                        // Auto close and reload if specified
                                        if (responseData.autoClose !== false || responseData.reload) {
                                            setTimeout(function () {
                                                WPFlyout.close(flyoutId);

                                                if (responseData.reload) {
                                                    location.reload();
                                                }
                                            }, 1500);
                                        }
                                    } else {
                                        // Show error alert
                                        if (window.WPFlyoutAlert) {
                                            WPFlyoutAlert.show(
                                                saveResponse.data || 'An error occurred. Please try again.',
                                                'error',
                                                {
                                                    target: $flyout.find('.wp-flyout-body'),
                                                    prepend: true,
                                                    dismissible: true
                                                }
                                            );
                                        }
                                    }
                                })
                                .fail(function () {
                                    $saveBtn.prop('disabled', false).text(originalText);

                                    if (window.WPFlyoutAlert) {
                                        WPFlyoutAlert.show(
                                            'Connection error. Please check your internet connection and try again.',
                                            'error',
                                            {
                                                target: $flyout.find('.wp-flyout-body'),
                                                prepend: true,
                                                dismissible: true
                                            }
                                        );
                                    }
                                });
                        });

                        // Bind delete button
                        $flyout.on('click', '.wp-flyout-delete', function (e) {
                            e.preventDefault();

                            const $deleteBtn = $(this);
                            const originalText = $deleteBtn.text();

                            $deleteBtn.prop('disabled', true).text('Deleting...');

                            // Get hidden ID field
                            const deleteId = $flyout.find('input[name="id"]').val() || data.id;

                            $.post(ajaxurl, {
                                action: 'wp_flyout_' + manager,
                                handler: handler,
                                handler_action: 'delete',
                                nonce: nonce,
                                ...data,
                                id: deleteId
                            })
                                .done(function (response) {
                                    if (response.success) {
                                        if (window.WPFlyoutAlert) {
                                            WPFlyoutAlert.show(
                                                response.data.message || 'Item deleted successfully!',
                                                'success',
                                                {
                                                    target: $flyout.find('.wp-flyout-body'),
                                                    prepend: true,
                                                    timeout: 2000
                                                }
                                            );
                                        }

                                        setTimeout(function () {
                                            WPFlyout.close(flyoutId);
                                            if (response.data.reload) {
                                                location.reload();
                                            }
                                        }, 1000);
                                    } else {
                                        $deleteBtn.prop('disabled', false).text(originalText);

                                        if (window.WPFlyoutAlert) {
                                            WPFlyoutAlert.show(
                                                response.data || 'Failed to delete item',
                                                'error',
                                                {
                                                    target: $flyout.find('.wp-flyout-body'),
                                                    prepend: true,
                                                    dismissible: true
                                                }
                                            );
                                        }
                                    }
                                })
                                .fail(function () {
                                    $deleteBtn.prop('disabled', false).text(originalText);

                                    if (window.WPFlyoutAlert) {
                                        WPFlyoutAlert.show(
                                            'Connection error. Could not delete item.',
                                            'error',
                                            {
                                                target: $flyout.find('.wp-flyout-body'),
                                                prepend: true,
                                                dismissible: true
                                            }
                                        );
                                    }
                                });
                        });

                        // Bind close button
                        $flyout.on('click', '.wp-flyout-close', function (e) {
                            e.preventDefault();
                            WPFlyout.close(flyoutId);
                        });

                    } else {
                        // Show error in a basic alert if flyout fails to load
                        alert(response.data || 'Failed to load flyout');
                    }
                })
                .fail(function () {
                    alert('Failed to connect to server');
                });
        });

    });

})(jQuery);