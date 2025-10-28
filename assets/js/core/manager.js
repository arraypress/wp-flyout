/**
 * WP Flyout Manager JavaScript
 * Automatic flyout orchestration based on data attributes
 *
 * @version 2.0.0 - Fixed
 */
(function ($) {
    'use strict';

    /**
     * Flyout Manager
     */
    window.WPFlyoutManager = window.WPFlyoutManager || {};
    WPFlyoutManager.instances = {};

    /**
     * Manager Instance
     */
    class FlyoutManagerInstance {
        constructor(prefix, config) {
            this.prefix = prefix;
            this.config = config;
            this.currentFlyout = null;
            this.currentHandler = null;
            this.currentHandlerId = null;
            this.currentNonce = null;
            this.currentTrigger = null;

            this.init();
        }

        /**
         * Initialize the manager
         */
        init() {
            this.bindTriggers();
        }

        /**
         * Bind trigger elements
         */
        bindTriggers() {
            const self = this;

            // Use event delegation for dynamic elements
            // NOTE: PHP generates data-flyout-manager and data-flyout-handler
            $(document).on('click', `.wp-flyout-trigger[data-flyout-manager="${this.prefix}"]`, function (e) {
                e.preventDefault();

                const $trigger = $(this);
                self.currentTrigger = $trigger;

                // Get the correct attributes that PHP generates
                const handlerId = $trigger.data('flyout-handler');
                const nonce = $trigger.data('flyout-nonce');

                if (!handlerId) {
                    console.error('WP Flyout: No handler ID found on trigger');
                    return;
                }

                // Collect all data attributes
                const data = {};
                $.each($trigger[0].dataset, function (key, value) {
                    // Skip flyout-specific attributes
                    if (key !== 'flyoutManager' && key !== 'flyoutHandler' && key !== 'flyoutNonce') {
                        // Convert from camelCase to snake_case for PHP
                        const snakeKey = key.replace(/([A-Z])/g, '_$1').toLowerCase();
                        data['data_' + snakeKey] = value;
                    }
                });

                self.open(handlerId, nonce, data);
            });
        }

        /**
         * Open a flyout
         */
        open(handlerId, nonce, data = {}) {
            const self = this;
            const handler = this.config.handlers ? this.config.handlers[handlerId] : null;

            if (!handler) {
                console.error('WP Flyout: Handler configuration not found:', handlerId);
                return;
            }

            this.currentHandler = handler;
            this.currentHandlerId = handlerId;
            this.currentNonce = nonce;

            // Show loading state (could use a temporary loading div)
            const loadingHtml = `
                <div class="wp-flyout-loading">
                    <span class="spinner is-active"></span>
                    <p>${this.config.strings.loading || 'Loading...'}</p>
                </div>
            `;

            // Load content via AJAX first
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler: handlerId,
                    handler_action: 'load',
                    nonce: nonce,
                    ...data
                },
                success: function (response) {
                    if (response.success && response.data) {
                        // Create temporary div with the flyout HTML
                        const $temp = $('<div>').html(response.data.html);
                        const $flyoutHtml = $temp.find('.wp-flyout').first();

                        if ($flyoutHtml.length) {
                            // Add the flyout HTML to the page
                            $('body').append($flyoutHtml);

                            // Get the flyout ID from the rendered HTML
                            const flyoutId = $flyoutHtml.attr('id');

                            // Open using the legacy method (string ID)
                            const result = WPFlyout.open(flyoutId);

                            if (result) {
                                self.currentFlyout = $('#' + flyoutId);
                                self.setupFlyout();
                            }
                        } else {
                            console.error('WP Flyout: No flyout HTML in response');
                        }
                    } else {
                        alert(response.data || self.config.strings.error || 'Error loading flyout');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('WP Flyout AJAX Error:', error);
                    alert(self.config.strings.error || 'Error loading flyout');
                }
            });
        }

        /**
         * Setup the flyout after it's opened
         */
        setupFlyout() {
            const self = this;
            const $flyout = this.currentFlyout;
            const handler = this.currentHandler;

            if (!$flyout || !$flyout.length) {
                return;
            }

            // Bind save button
            $flyout.find('.wp-flyout-save').off('click.manager').on('click.manager', function (e) {
                e.preventDefault();
                self.handleSave();
            });

            // Bind delete button
            $flyout.find('.wp-flyout-delete').off('click.manager').on('click.manager', function (e) {
                e.preventDefault();
                if (confirm(self.config.strings.confirmDelete || 'Are you sure you want to delete this item?')) {
                    self.handleDelete();
                }
            });

            // Bind custom action buttons
            $flyout.find('[data-flyout-action]').off('click.manager').on('click.manager', function (e) {
                e.preventDefault();
                const $button = $(this);
                const action = $button.data('flyout-action');
                self.handleCustomAction(action, {}, $button);
            });

            // Bind form submission
            $flyout.find('form').off('submit.manager').on('submit.manager', function (e) {
                e.preventDefault();
                self.handleSave();
            });

            // Track dirty state if configured
            if (handler.trackDirty) {
                $flyout.find('form :input').on('change.dirty input.dirty', function () {
                    $flyout.data('isDirty', true);
                });
            }

            // Trigger ready event
            $(document).trigger('wpflyout:ready', {
                element: $flyout[0],
                handler: handler
            });
        }

        /**
         * Handle save action
         */
        handleSave() {
            const self = this;
            const $flyout = this.currentFlyout;
            const $form = $flyout.find('form').first();

            if (!$form.length) {
                console.warn('WP Flyout: No form found in flyout');
                return;
            }

            const $saveBtn = $flyout.find('.wp-flyout-save');
            const originalText = $saveBtn.text();

            // Show saving state
            $saveBtn.prop('disabled', true).text(this.config.strings.saving || 'Saving...');

            // Collect form data
            const formData = $form.serialize();

            // Get original data attributes from trigger
            const data = this.getCurrentData();

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler: this.currentHandlerId,
                    handler_action: 'save',
                    form_data: formData,
                    nonce: this.currentNonce,
                    ...data
                },
                success: function (response) {
                    $saveBtn.prop('disabled', false).text(originalText);

                    if (response.success) {
                        self.handleSaveSuccess(response.data);
                    } else {
                        self.handleSaveError(response.data);
                    }
                },
                error: function () {
                    $saveBtn.prop('disabled', false).text(originalText);
                    self.handleSaveError(self.config.strings.error || 'Save failed');
                }
            });
        }

        /**
         * Handle save success
         */
        handleSaveSuccess(data) {
            const handler = this.currentHandler;
            const $flyout = this.currentFlyout;
            const message = data.message || handler.successMessage || 'Saved successfully!';

            // Show success message
            this.showMessage(message, 'success');

            // Reset dirty flag
            $flyout.data('isDirty', false);

            // Trigger saved event
            $(document).trigger('wpflyout:saved', {
                element: $flyout[0],
                data: data
            });

            // Auto close if configured
            if (handler.autoClose) {
                setTimeout(() => {
                    WPFlyout.close($flyout.attr('id'));

                    // Refresh if configured
                    if (handler.refresh) {
                        window.location.reload();
                    }
                }, 1500);
            }
        }

        /**
         * Handle save error
         */
        handleSaveError(message) {
            message = message || this.config.strings.error || 'Save failed';
            this.showMessage(message, 'error');
        }

        /**
         * Handle delete action
         */
        handleDelete() {
            const self = this;
            const $flyout = this.currentFlyout;
            const data = this.getCurrentData();

            const $deleteBtn = $flyout.find('.wp-flyout-delete');
            const originalText = $deleteBtn.text();

            $deleteBtn.prop('disabled', true).text(this.config.strings.deleting || 'Deleting...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler: this.currentHandlerId,
                    handler_action: 'delete',
                    nonce: this.currentNonce,
                    ...data
                },
                success: function (response) {
                    $deleteBtn.prop('disabled', false).text(originalText);

                    if (response.success) {
                        self.showMessage(response.data.message || 'Deleted successfully!', 'success');

                        setTimeout(() => {
                            WPFlyout.close($flyout.attr('id'));
                            if (self.currentHandler.refresh) {
                                window.location.reload();
                            }
                        }, 1500);
                    } else {
                        self.showMessage(response.data || 'Delete failed', 'error');
                    }
                },
                error: function () {
                    $deleteBtn.prop('disabled', false).text(originalText);
                    self.showMessage('Delete failed', 'error');
                }
            });
        }

        /**
         * Handle custom action
         */
        handleCustomAction(action, actionData, $button) {
            const self = this;
            const data = this.getCurrentData();

            const originalText = $button.text();
            $button.prop('disabled', true).text('Processing...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler: this.currentHandlerId,
                    handler_action: action,
                    nonce: this.currentNonce,
                    ...data,
                    ...actionData
                },
                success: function (response) {
                    $button.prop('disabled', false).text(originalText);

                    if (response.success) {
                        // Trigger custom event
                        $(document).trigger(`wpflyout:${action}`, {
                            element: self.currentFlyout[0],
                            data: response.data
                        });

                        // Show message if provided
                        if (response.data && response.data.message) {
                            self.showMessage(response.data.message, 'success');
                        }
                    } else {
                        self.showMessage(response.data || 'Action failed', 'error');
                    }
                },
                error: function () {
                    $button.prop('disabled', false).text(originalText);
                    self.showMessage('Action failed', 'error');
                }
            });
        }

        /**
         * Show message in flyout
         */
        showMessage(message, type) {
            const $flyout = this.currentFlyout;
            const $content = $flyout.find('.wp-flyout-body').first();

            // Remove existing notices
            $content.find('.notice').remove();

            // Add new notice
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const $notice = $(`
                <div class="notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                </div>
            `).prependTo($content);

            // Auto dismiss success messages
            if (type === 'success') {
                setTimeout(() => {
                    $notice.fadeOut(() => $notice.remove());
                }, 3000);
            }
        }

        /**
         * Get current data attributes from trigger
         */
        getCurrentData() {
            const data = {};

            if (this.currentTrigger) {
                $.each(this.currentTrigger[0].dataset, function (key, value) {
                    if (key !== 'flyoutManager' && key !== 'flyoutHandler' && key !== 'flyoutNonce') {
                        const snakeKey = key.replace(/([A-Z])/g, '_$1').toLowerCase();
                        data['data_' + snakeKey] = value;
                    }
                });
            }

            return data;
        }
    }

    /**
     * Initialize managers for each prefix
     */
    $(document).ready(function () {
        // Look for all localized configs
        for (let key in window) {
            if (key.startsWith('wpFlyoutManager_')) {
                const prefix = key.replace('wpFlyoutManager_', '').replace(/_/g, '-');
                const config = window[key];

                // Create manager instance
                WPFlyoutManager.instances[prefix] = new FlyoutManagerInstance(prefix, config);

                console.log('WP Flyout Manager initialized:', prefix);
            }
        }
    });

})(jQuery);