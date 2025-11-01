/**
 * WP Flyout Manager - Simplified version
 */
(function ($) {
    'use strict';

    const WPFlyoutManager = {

        /**
         * Initialize manager
         */
        init: function () {
            $(document).on('click', '.wp-flyout-trigger', this.handleTrigger.bind(this));
        },

        /**
         * Handle trigger click
         */
        handleTrigger: function (e) {
            e.preventDefault();

            const $btn = $(e.currentTarget);
            const config = this.extractConfig($btn);

            this.loadFlyout(config);
        },

        /**
         * Extract configuration from trigger button
         */
        extractConfig: function ($btn) {
            const config = {
                flyout: $btn.data('flyout'),
                manager: $btn.data('flyout-manager'),
                nonce: $btn.data('flyout-nonce'),
                data: {}
            };

            // Collect additional data attributes
            $.each($btn[0].dataset, (key, value) => {
                if (!['flyout', 'flyoutManager', 'flyoutNonce'].includes(key)) {
                    config.data[key] = value;
                }
            });

            return config;
        },

        /**
         * Load flyout via AJAX
         */
        loadFlyout: function (config) {
            $.post(ajaxurl, {
                action: 'wp_flyout_' + config.manager,
                flyout: config.flyout,
                flyout_action: 'load',
                nonce: config.nonce,
                ...config.data
            })
                .done(response => {
                    if (response.success) {
                        this.displayFlyout(response.data.html, config);
                    } else {
                        alert(response.data || 'Failed to load flyout');
                    }
                })
                .fail(() => alert('Connection failed'));
        },

        /**
         * Display flyout and setup handlers
         */
        displayFlyout: function (html, config) {
            // Remove existing flyouts and add new one
            $('.wp-flyout').remove();
            $('body').append(html);

            const $flyout = $('.wp-flyout').last();
            const flyoutId = $flyout.attr('id');

            // Open it
            WPFlyout.open(flyoutId);

            // Store config
            $flyout.data(config);

            // Ensure form wrapper exists
            this.ensureForm($flyout);

            // Bind handlers
            this.bindHandlers($flyout, flyoutId, config);
        },

        /**
         * Ensure form wrapper exists
         */
        ensureForm: function ($flyout) {
            if (!$flyout.find('form').length) {
                const $body = $flyout.find('.wp-flyout-body');
                const $form = $('<form class="wp-flyout-form" novalidate></form>');
                $form.append($body.children());
                $body.append($form);
            }
        },

        /**
         * Bind event handlers
         */
        bindHandlers: function ($flyout, flyoutId, config) {
            // Save button
            $flyout.on('click', '.wp-flyout-save', e => {
                e.preventDefault();
                this.handleSave($flyout, flyoutId, config);
            });

            // Delete button
            $flyout.on('click', '.wp-flyout-delete', e => {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this item?')) {
                    this.handleDelete($flyout, flyoutId, config);
                }
            });

            // Close button (redundant since flyout.js handles this, but kept for safety)
            $flyout.on('click', '.wp-flyout-close', e => {
                e.preventDefault();
                WPFlyout.close(flyoutId);
            });

            // Clear error class on change
            $flyout.on('input change', '.error', function () {
                $(this).removeClass('error');
            });
        },

        /**
         * Validate form
         */
        validateForm: function ($form) {
            let isValid = true;
            let firstInvalid = null;

            $form.find('[required]:visible:enabled').each(function () {
                const $field = $(this);
                const value = $field.val();

                if (!value || (Array.isArray(value) && !value.length)) {
                    isValid = false;
                    $field.addClass('error');
                    firstInvalid = firstInvalid || $field;
                } else {
                    $field.removeClass('error');
                }
            });

            return {isValid, firstInvalid};
        },

        /**
         * Handle save
         */
        handleSave: function ($flyout, flyoutId, config) {
            const $form = $flyout.find('form').first();
            const $saveBtn = $flyout.find('.wp-flyout-save');

            // Validate
            const validation = this.validateForm($form);
            if (!validation.isValid) {
                this.showAlert($flyout, 'Please fill in all required fields.', 'error');
                if (validation.firstInvalid) {
                    validation.firstInvalid.focus();
                }
                return;
            }

            // Save
            this.setButtonState($saveBtn, true, 'Saving...');

            $.post(ajaxurl, {
                action: 'wp_flyout_' + config.manager,
                flyout: config.flyout,
                flyout_action: 'save',
                nonce: config.nonce,
                form_data: $form.serialize(),
                ...config.data
            })
                .done(response => {
                    this.setButtonState($saveBtn, false);

                    if (response.success) {
                        const data = response.data || {};
                        this.showAlert($flyout, data.message || 'Saved successfully!', 'success');

                        // Close after delay
                        setTimeout(() => {
                            WPFlyout.close(flyoutId);
                            if (data.reload) location.reload();
                        }, 1500);
                    } else {
                        this.showAlert($flyout, response.data || 'An error occurred', 'error');
                    }
                })
                .fail(() => {
                    this.setButtonState($saveBtn, false);
                    this.showAlert($flyout, 'Connection error', 'error');
                });
        },

        /**
         * Handle delete
         */
        handleDelete: function ($flyout, flyoutId, config) {
            const $deleteBtn = $flyout.find('.wp-flyout-delete');
            const deleteId = $flyout.find('input[name="id"]').val() || config.data.id;

            this.setButtonState($deleteBtn, true, 'Deleting...');

            $.post(ajaxurl, {
                action: 'wp_flyout_' + config.manager,
                flyout: config.flyout,
                flyout_action: 'delete',
                nonce: config.nonce,
                id: deleteId,
                ...config.data
            })
                .done(response => {
                    if (response.success) {
                        const data = response.data || {};
                        this.showAlert($flyout, data.message || 'Deleted successfully!', 'success');

                        setTimeout(() => {
                            WPFlyout.close(flyoutId);
                            if (data.reload) location.reload();
                        }, 1000);
                    } else {
                        this.setButtonState($deleteBtn, false);
                        this.showAlert($flyout, response.data || 'Failed to delete', 'error');
                    }
                })
                .fail(() => {
                    this.setButtonState($deleteBtn, false);
                    this.showAlert($flyout, 'Connection error', 'error');
                });
        },

        /**
         * Show alert message
         */
        showAlert: function ($flyout, message, type) {
            if (window.WPFlyoutAlert) {
                WPFlyoutAlert.show(message, type, {
                    target: $flyout.find('.wp-flyout-body'),
                    prepend: true,
                    timeout: type === 'success' ? 3000 : 0,
                    dismissible: true
                });
            }
        },

        /**
         * Set button loading state
         */
        setButtonState: function ($btn, disabled, text) {
            if (!$btn.length) return;

            if (disabled) {
                $btn.data('original-text', $btn.text());
                $btn.prop('disabled', true).text(text);
            } else {
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Save');
            }
        }
    };

    // Initialize
    $(document).ready(() => WPFlyoutManager.init());

})(jQuery);