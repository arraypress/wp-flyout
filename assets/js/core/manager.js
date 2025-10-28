/**
 * WP Flyout Manager JavaScript
 * Automatic flyout orchestration based on data attributes
 *
 * @version 1.0.0
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
            $(document).on('click', `.wp-flyout-trigger[data-flyout-prefix="${this.prefix}"]`, function (e) {
                e.preventDefault();

                const $trigger = $(this);
                const handlerId = $trigger.data('flyout-id');
                const nonce = $trigger.data('flyout-nonce');

                // Collect all data attributes
                const data = {};
                $.each($trigger[0].dataset, function (key, value) {
                    if (key !== 'flyoutPrefix' && key !== 'flyoutId' && key !== 'flyoutNonce') {
                        // Convert camelCase to snake_case for PHP
                        const snakeKey = key.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
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
            const handler = this.config.handlers[handlerId];

            if (!handler) {
                console.error('WP Flyout: Handler not found:', handlerId);
                return;
            }

            this.currentHandler = handler;
            this.currentHandlerId = handlerId;
            this.currentNonce = nonce;

            // Create flyout with loading state
            const flyoutId = `flyout-${this.prefix}-${handlerId}`;
            const $flyout = WPFlyout.open({
                id: flyoutId,
                title: handler.title,
                width: handler.width,
                position: handler.position || 'right'
            });

            this.currentFlyout = $flyout;

            // Show loading
            WPFlyout.showLoading($flyout, this.config.strings.loading);

            // Load content via AJAX
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler_id: handlerId,
                    flyout_action: 'load',
                    _wpnonce: nonce,
                    ...data
                },
                success: function (response) {
                    if (response.success) {
                        self.displayContent(response.data);
                    } else {
                        WPFlyout.showError($flyout, response.data || self.config.strings.error);
                    }
                },
                error: function (xhr, status, error) {
                    WPFlyout.showError($flyout, self.config.strings.error);
                    console.error('WP Flyout AJAX Error:', error);
                }
            });
        }

        /**
         * Display the flyout content
         */
        displayContent(data) {
            const self = this;
            const $flyout = this.currentFlyout;
            const handler = this.currentHandler;

            // Set content
            WPFlyout.setContent($flyout, data.html);

            // Update title if provided
            if (data.title) {
                WPFlyout.setTitle($flyout, data.title);
            }

            // Update width if provided
            if (data.width) {
                WPFlyout.setWidth($flyout, data.width);
            }

            // Show/hide footer based on configuration
            if (data.showFooter === false || handler.showFooter === false) {
                $flyout.find('.wp-flyout-footer').hide();
            } else if (!$flyout.find('.wp-flyout-footer').children().length) {
                // Add default footer buttons if not already in content
                this.addDefaultFooter($flyout);
            }

            // Bind form handling
            this.bindFormHandling($flyout);

            // Track dirty state
            WPFlyout.trackDirty($flyout);

            // Bind custom actions
            this.bindCustomActions($flyout);

            // Trigger content loaded event
            $(document).trigger('wpflyout:contentloaded', {
                element: $flyout[0],
                handler: handler,
                data: data
            });
        }

        /**
         * Add default footer buttons
         */
        addDefaultFooter($flyout) {
            const handler = this.currentHandler;
            const $footer = $flyout.find('.wp-flyout-footer');

            // Only add if footer is empty
            if ($footer.children().length > 0) {
                return;
            }

            let footerHtml = '';

            // Add save button if save handler exists
            if (handler.hasSave) {
                footerHtml += `
                    <button type="button" class="button button-primary wp-flyout-save">
                        <span class="dashicons dashicons-saved"></span>
                        ${this.config.strings.save}
                    </button>
                `;
            }

            // Add delete button if delete handler exists
            if (handler.hasDelete) {
                footerHtml += `
                    <button type="button" class="button button-link-delete wp-flyout-delete">
                        <span class="dashicons dashicons-trash"></span>
                        ${this.config.strings.delete}
                    </button>
                `;
            }

            // Add close button
            footerHtml += `
                <button type="button" class="button wp-flyout-close">
                    ${this.config.strings.close}
                </button>
            `;

            $footer.html(footerHtml).show();

            // Bind footer events
            this.bindFooterEvents($flyout);
        }

        /**
         * Bind form handling
         */
        bindFormHandling($flyout) {
            const self = this;

            // Auto-bind form submission
            WPFlyout.bindFormAutoSubmit($flyout, function ($form) {
                self.handleFormSubmit($form);
            });

            // Also bind to save button click
            $flyout.find('.wp-flyout-save').off('click.manager').on('click.manager', function () {
                const $form = $flyout.find('form').first();
                if ($form.length) {
                    self.handleFormSubmit($form);
                } else {
                    // No form, just call save with empty data
                    self.handleFormSubmit(null);
                }
            });
        }

        /**
         * Bind footer events
         */
        bindFooterEvents($flyout) {
            const self = this;

            // Delete button
            $flyout.find('.wp-flyout-delete').off('click.manager').on('click.manager', function () {
                if (confirm(self.config.strings.confirmDelete)) {
                    self.handleDelete();
                }
            });

            // Close button
            $flyout.find('.wp-flyout-close').off('click.manager').on('click.manager', function () {
                // Check dirty state
                if (WPFlyout.isDirty($flyout)) {
                    if (!confirm(self.config.strings.confirmClose)) {
                        return;
                    }
                }
                WPFlyout.close($flyout);
            });
        }

        /**
         * Bind custom action buttons
         */
        bindCustomActions($flyout) {
            const self = this;

            $flyout.find('[data-flyout-action]').off('click.manager').on('click.manager', function () {
                const $button = $(this);
                const action = $button.data('flyout-action');

                // Get any additional data attributes
                const actionData = {};
                $.each($button[0].dataset, function (key, value) {
                    if (key !== 'flyoutAction') {
                        const snakeKey = key.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
                        actionData['data_' + snakeKey] = value;
                    }
                });

                self.handleCustomAction(action, actionData, $button);
            });
        }

        /**
         * Handle form submission
         */
        handleFormSubmit($form) {
            const self = this;
            const handler = this.currentHandler;
            const $flyout = this.currentFlyout;

            // Check if save handler exists
            if (!handler.hasSave) {
                console.warn('WP Flyout: No save handler defined for', this.currentHandlerId);
                return;
            }

            // Show saving state
            const $saveBtn = $flyout.find('.wp-flyout-save');
            const originalText = $saveBtn.html();
            $saveBtn.prop('disabled', true).html(
                `<span class="spinner is-active"></span> ${this.config.strings.saving}`
            );

            // Collect form data
            const formData = $form ? $form.serialize() : '';

            // Get original data attributes
            const data = this.getCurrentData();

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler_id: this.currentHandlerId,
                    flyout_action: 'save',
                    form_data: formData,
                    _wpnonce: $form ? ($form.find('[name="_wpnonce"]').val() || this.currentNonce) : this.currentNonce,
                    ...data
                },
                success: function (response) {
                    $saveBtn.prop('disabled', false).html(originalText);

                    if (response.success) {
                        self.handleSaveSuccess(response.data);
                    } else {
                        self.handleSaveError(response.data);
                    }
                },
                error: function () {
                    $saveBtn.prop('disabled', false).html(originalText);
                    self.handleSaveError(self.config.strings.error);
                }
            });
        }

        /**
         * Handle save success
         */
        handleSaveSuccess(data) {
            const handler = this.currentHandler;
            const $flyout = this.currentFlyout;
            const message = data.message || handler.successMessage || this.config.strings.saved;

            // Show success message
            WPFlyout.showMessage($flyout, message, 'success');

            // Reset dirty flag
            WPFlyout.resetDirty($flyout);

            // Trigger success event
            $(document).trigger('wpflyout:saved', {
                element: $flyout[0],
                data: data
            });

            // Handle reload if requested
            if (data.reload) {
                this.reloadContent();
                return;
            }

            // Auto close if configured
            if (handler.autoClose) {
                setTimeout(() => {
                    WPFlyout.close($flyout);

                    // Refresh if configured
                    if (handler.refresh) {
                        if (handler.refreshTarget) {
                            // Refresh specific element
                            WPFlyout.refreshElement(handler.refreshTarget);
                        } else {
                            // Refresh page
                            window.location.reload();
                        }
                    }
                }, 1500);
            }
        }

        /**
         * Handle save error
         */
        handleSaveError(message) {
            const handler = this.currentHandler;
            const $flyout = this.currentFlyout;

            message = message || handler.errorMessage || this.config.strings.error;
            WPFlyout.showMessage($flyout, message, 'error');
        }

        /**
         * Handle delete action
         */
        handleDelete() {
            const self = this;
            const handler = this.currentHandler;
            const $flyout = this.currentFlyout;

            const $deleteBtn = $flyout.find('.wp-flyout-delete');
            const originalText = $deleteBtn.html();
            $deleteBtn.prop('disabled', true).html(
                `<span class="spinner is-active"></span> ${this.config.strings.deleting}`
            );

            const data = this.getCurrentData();

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: `wp_flyout_${this.prefix}`,
                    handler_id: this.currentHandlerId,
                    flyout_action: 'delete',
                    _wpnonce: this.currentNonce,
                    ...data
                },
                success: function (response) {
                    $deleteBtn.prop('disabled', false).html(originalText);

                    if (response.success) {
                        WPFlyout.showMessage($flyout, response.data.message || self.config.strings.deleted, 'success');

                        setTimeout(() => {
                            WPFlyout.close($flyout);
                            if (handler.refresh) {
                                window.location.reload();
                            }
                        }, 1500);
                    } else {
                        WPFlyout.showMessage($flyout, response.data || self.config.strings.error, 'error');
                    }
                },
                error: function () {
                    $deleteBtn.prop('disabled', false).html(originalText);
                    WPFlyout.showMessage($flyout, self.config.strings.error, 'error');
                }
            });
        }

        /**
         * Handle custom action
         */
        handleCustomAction(action, actionData, $button) {
            const self = this;
            const $flyout = this.currentFlyout;
            const data = this.getCurrentData();

            // Show loading on button
            const originalText = $button.html();
            $button.prop('disabled', true).html(
                `<span class="spinner is-active"></span> ${this.config.strings.loading}`
            );

            // Merge all data
            const requestData = {
                action: `wp_flyout_${this.prefix}`,
                handler_id: this.currentHandlerId,
                flyout_action: action,
                _wpnonce: this.currentNonce,
                ...data,
                ...actionData
            };

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: requestData,
                success: function (response) {
                    $button.prop('disabled', false).html(originalText);

                    if (response.success) {
                        // Trigger custom event
                        $(document).trigger(`wpflyout:${action}`, {
                            element: $flyout[0],
                            data: response.data
                        });

                        // Show message if provided
                        if (response.data && response.data.message) {
                            WPFlyout.showMessage($flyout, response.data.message, 'success');
                        }

                        // Reload content if requested
                        if (response.data && response.data.reload) {
                            self.reloadContent();
                        }
                    } else {
                        WPFlyout.showMessage($flyout, response.data || self.config.strings.error, 'error');
                    }
                },
                error: function () {
                    $button.prop('disabled', false).html(originalText);
                    WPFlyout.showMessage($flyout, self.config.strings.error, 'error');
                }
            });
        }

        /**
         * Reload flyout content
         */
        reloadContent() {
            const data = this.getCurrentData();
            WPFlyout.close(this.currentFlyout);
            this.open(this.currentHandlerId, this.currentNonce, data);
        }

        /**
         * Get current data attributes
         */
        getCurrentData() {
            const data = {};
            const $trigger = $(`.wp-flyout-trigger[data-flyout-id="${this.currentHandlerId}"][data-flyout-prefix="${this.prefix}"]`).first();

            if ($trigger.length) {
                $.each($trigger[0].dataset, function (key, value) {
                    if (key !== 'flyoutPrefix' && key !== 'flyoutId' && key !== 'flyoutNonce') {
                        const snakeKey = key.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
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
            }
        }
    });

})(jQuery);