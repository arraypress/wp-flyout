/**
 * WP Flyout Manager - Complete Working Version
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Handle trigger clicks
        $(document).on('click', '.wp-flyout-trigger', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const handler = $btn.data('flyout-handler');
            const manager = $btn.data('flyout-manager');
            const nonce = $btn.data('flyout-nonce');

            // Collect data attributes
            const data = {};
            $.each(this.dataset, function(key, value) {
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
                .done(function(response) {
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
                        $flyout.on('click', '.wp-flyout-save', function(e) {
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
                                .done(function(saveResponse) {
                                    $saveBtn.prop('disabled', false).text(originalText);

                                    if (saveResponse.success) {
                                        const responseData = saveResponse.data || {};

                                        // Show success alert
                                        showAlert($flyout, responseData.message || 'Saved!', 'success');

                                        // Auto close and reload if specified
                                        if (responseData.autoClose !== false || responseData.reload) {
                                            setTimeout(function() {
                                                WPFlyout.close(flyoutId);

                                                if (responseData.reload) {
                                                    location.reload();
                                                }
                                            }, 1500);
                                        }
                                    } else {
                                        // Show error alert
                                        showAlert($flyout, saveResponse.data || 'Save failed', 'error');
                                    }
                                })
                                .fail(function() {
                                    $saveBtn.prop('disabled', false).text(originalText);
                                    showAlert($flyout, 'Connection error. Please try again.', 'error');
                                });
                        });

                        // Bind delete button (without double confirm)
                        $flyout.on('click', '.wp-flyout-delete', function(e) {
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
                                .done(function(response) {
                                    if (response.success) {
                                        showAlert($flyout, response.data.message || 'Deleted!', 'success');

                                        setTimeout(function() {
                                            WPFlyout.close(flyoutId);
                                            if (response.data.reload) {
                                                location.reload();
                                            }
                                        }, 1000);
                                    } else {
                                        $deleteBtn.prop('disabled', false).text(originalText);
                                        showAlert($flyout, response.data || 'Delete failed', 'error');
                                    }
                                })
                                .fail(function() {
                                    $deleteBtn.prop('disabled', false).text(originalText);
                                    showAlert($flyout, 'Connection error', 'error');
                                });
                        });

                        // Bind close button
                        $flyout.on('click', '.wp-flyout-close', function(e) {
                            e.preventDefault();
                            WPFlyout.close(flyoutId);
                        });

                        // Bind alert dismiss
                        $flyout.on('click', '.alert-dismiss', function(e) {
                            e.preventDefault();
                            $(this).closest('.wp-flyout-alert').fadeOut(function() {
                                $(this).remove();
                            });
                        });

                    } else {
                        alert(response.data || 'Failed to load');
                    }
                })
                .fail(function() {
                    alert('Failed to load flyout');
                });
        });

        /**
         * Show alert message in flyout using nice Alert component style
         */
        function showAlert($flyout, message, type) {
            // Remove existing alerts
            $flyout.find('.wp-flyout-alert').remove();

            // Determine icon
            const icons = {
                'success': 'yes-alt',
                'error': 'dismiss',
                'warning': 'warning',
                'info': 'info'
            };
            const icon = icons[type] || 'info';

            // Build alert HTML matching Alert component
            const alertHtml = `
                <div class="wp-flyout-alert alert-${type} is-dismissible" role="alert">
                    <div class="alert-content-wrapper">
                        <div class="alert-icon">
                            <span class="dashicons dashicons-${icon}"></span>
                        </div>
                        <div class="alert-content">
                            <div class="alert-message">
                                ${message}
                            </div>
                        </div>
                        <button type="button" class="alert-dismiss" aria-label="Dismiss alert">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
            `;

            // Add to body
            $flyout.find('.wp-flyout-body').prepend(alertHtml);

            // Auto-dismiss success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $flyout.find('.wp-flyout-alert').fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
    });

})(jQuery);