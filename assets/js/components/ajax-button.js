/**
 * WP Flyout Ajax Button Component
 * Handles AJAX button operations with loading states and notifications
 */
(function ($) {
    'use strict';

    const AjaxButton = {

        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('click', '.wp-flyout-ajax-button', function (e) {
                e.preventDefault();
                AjaxButton.handleClick($(this));
            });
        },

        handleClick: function ($button) {
            // Check if already processing
            if ($button.hasClass('processing')) {
                return;
            }

            // Get button data
            const action = $button.data('action');
            const nonce = $button.data('nonce');
            const confirm_msg = $button.data('confirm');
            const params = $button.data('params') || {};
            const callback = $button.data('callback');
            const showNotice = $button.data('show-notice') !== false;
            const noticeLocation = $button.data('notice-location') || 'flyout';

            // Show confirmation if needed
            if (confirm_msg && !confirm(confirm_msg)) {
                return;
            }

            // Show loading state
            this.setLoading($button, true);

            // Prepare AJAX data
            const ajaxData = {
                action: action,
                _wpnonce: nonce,
                ...params
            };

            // Make AJAX request
            $.ajax({
                url: wpFlyoutConfig.ajaxUrl || ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function (response) {
                    AjaxButton.setLoading($button, false);

                    if (response.success) {
                        // Show success notice
                        if (showNotice) {
                            AjaxButton.showNotice(
                                $button.data('success-text') || response.data.message || 'Success',
                                'success',
                                noticeLocation
                            );
                        }

                        // Call custom callback if provided
                        if (callback && typeof window[callback] === 'function') {
                            window[callback](response.data, $button);
                        }

                        // Trigger custom event
                        $button.trigger('ajax-success', [response.data]);

                        // Update button if needed
                        if (response.data.button_state) {
                            AjaxButton.updateButtonState($button, response.data.button_state);
                        }
                    } else {
                        // Show error notice
                        if (showNotice) {
                            AjaxButton.showNotice(
                                response.data || $button.data('error-text') || 'An error occurred',
                                'error',
                                noticeLocation
                            );
                        }

                        // Trigger error event
                        $button.trigger('ajax-error', [response.data]);
                    }
                },
                error: function (xhr, status, error) {
                    AjaxButton.setLoading($button, false);

                    if (showNotice) {
                        AjaxButton.showNotice(
                            'Network error: ' + error,
                            'error',
                            noticeLocation
                        );
                    }

                    $button.trigger('ajax-error', [error]);
                }
            });
        },

        setLoading: function ($button, loading) {
            if (loading) {
                const loadingText = $button.data('loading-text') || 'Processing...';
                $button.addClass('processing')
                    .prop('disabled', true);

                // Store original text
                $button.data('original-text', $button.find('.button-text').text());
                $button.find('.button-text').text(loadingText);

                // Show spinner if configured
                if ($button.data('show-spinner') !== false) {
                    $button.find('.button-spinner').show();
                }
            } else {
                $button.removeClass('processing')
                    .prop('disabled', false);

                // Restore original text
                const originalText = $button.data('original-text');
                if (originalText) {
                    $button.find('.button-text').text(originalText);
                }

                // Hide spinner
                $button.find('.button-spinner').hide();
            }
        },

        showNotice: function (message, type, location) {
            const $notice = $('<div class="wp-flyout-ajax-notice notice notice-' + type + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>');

            let $container;

            switch (location) {
                case 'table':
                    // Show above the table
                    $container = $('.wp-header-end').first();
                    if ($container.length) {
                        $container.after($notice);
                    }
                    break;

                case 'inline':
                    // Show inline near the button
                    $container = $button.parent();
                    $container.prepend($notice);
                    break;

                case 'flyout':
                default:
                    // Show at top of flyout
                    $container = $('.wp-flyout.active .wp-flyout-body').first();
                    if ($container.length) {
                        // Remove existing notices
                        $container.find('.wp-flyout-ajax-notice').remove();
                        $container.prepend($notice);
                    }
                    break;
            }

            // Auto-dismiss success notices
            if (type === 'success') {
                setTimeout(function () {
                    $notice.fadeOut(function () {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Handle dismiss button
            $notice.find('.notice-dismiss').on('click', function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            });
        },

        updateButtonState: function ($button, state) {
            if (state.disabled) {
                $button.prop('disabled', true).addClass('disabled');
            }

            if (state.text) {
                $button.find('.button-text').text(state.text);
            }

            if (state.class) {
                $button.addClass(state.class);
            }

            if (state.hide) {
                $button.hide();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        AjaxButton.init();
    });

    // Export for external use
    window.WPFlyoutAjaxButton = AjaxButton;

})(jQuery);