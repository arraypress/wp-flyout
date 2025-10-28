/**
 * WP Flyout Manager - Working Version
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        console.log('WP Flyout Manager initialized');

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

                        // Store handler info on flyout
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
                                        // Show success message
                                        showMessage($flyout, saveResponse.data.message || 'Saved!', 'success');

                                        // Handle auto-close
                                        if (saveResponse.data.autoClose !== false) {
                                            setTimeout(function () {
                                                WPFlyout.close(flyoutId);

                                                // Reload if needed
                                                if (saveResponse.data.reload) {
                                                    location.reload();
                                                }
                                            }, 1500);
                                        }
                                    } else {
                                        showMessage($flyout, saveResponse.data || 'Save failed', 'error');
                                    }
                                })
                                .fail(function () {
                                    $saveBtn.prop('disabled', false).text(originalText);
                                    showMessage($flyout, 'Save failed', 'error');
                                });
                        });

                        // Bind delete button
                        $flyout.on('click', '.wp-flyout-delete', function (e) {
                            e.preventDefault();

                            if (!confirm('Are you sure?')) {
                                return;
                            }

                            const $deleteBtn = $(this);
                            const originalText = $deleteBtn.text();

                            $deleteBtn.prop('disabled', true).text('Deleting...');

                            $.post(ajaxurl, {
                                action: 'wp_flyout_' + manager,
                                handler: handler,
                                handler_action: 'delete',
                                nonce: nonce,
                                ...data
                            })
                                .done(function (response) {
                                    if (response.success) {
                                        showMessage($flyout, response.data.message || 'Deleted!', 'success');

                                        setTimeout(function () {
                                            WPFlyout.close(flyoutId);
                                            if (response.data.reload) {
                                                location.reload();
                                            }
                                        }, 1000);
                                    } else {
                                        $deleteBtn.prop('disabled', false).text(originalText);
                                        showMessage($flyout, response.data || 'Delete failed', 'error');
                                    }
                                })
                                .fail(function () {
                                    $deleteBtn.prop('disabled', false).text(originalText);
                                    showMessage($flyout, 'Delete failed', 'error');
                                });
                        });

                        // Bind close button
                        $flyout.on('click', '.wp-flyout-close', function (e) {
                            e.preventDefault();
                            WPFlyout.close(flyoutId);
                        });

                        // Bind custom actions
                        $flyout.on('click', '[data-flyout-action]', function (e) {
                            e.preventDefault();

                            const $actionBtn = $(this);
                            const action = $actionBtn.data('flyout-action');
                            const originalText = $actionBtn.text();

                            $actionBtn.prop('disabled', true).text('Processing...');

                            $.post(ajaxurl, {
                                action: 'wp_flyout_' + manager,
                                handler: handler,
                                handler_action: action,
                                nonce: nonce,
                                ...data
                            })
                                .done(function (response) {
                                    $actionBtn.prop('disabled', false).text(originalText);

                                    if (response.success) {
                                        showMessage($flyout, response.data.message || 'Done!', 'success');
                                    } else {
                                        showMessage($flyout, response.data || 'Action failed', 'error');
                                    }
                                })
                                .fail(function () {
                                    $actionBtn.prop('disabled', false).text(originalText);
                                    showMessage($flyout, 'Action failed', 'error');
                                });
                        });

                    } else {
                        alert(response.data || 'Failed to load');
                    }
                })
                .fail(function () {
                    alert('Failed to load flyout');
                });
        });

        // Helper function to show messages
        function showMessage($flyout, message, type) {
            // Remove existing messages
            $flyout.find('.flyout-notice').remove();

            // Add new message
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const $notice = $('<div class="flyout-notice notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            // Add to body
            $flyout.find('.wp-flyout-body').prepend($notice);

            // Auto-dismiss success messages
            if (type === 'success') {
                setTimeout(function () {
                    $notice.fadeOut(function () {
                        $(this).remove();
                    });
                }, 3000);
            }
        }
    });

})(jQuery);