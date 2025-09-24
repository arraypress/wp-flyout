/**
 * WP Flyout Core JavaScript
 * Core flyout functionality only
 *
 * @package ArrayPress/WPFlyout
 * @version 2.0.0
 */
(function ($, window, document) {
    'use strict';

    /**
     * Main flyout manager
     */
    const WPFlyout = {

        activeInstances: {},

        defaults: {
            onOpen: null,
            onClose: null,
            onSave: null,
            closeOnEscape: true,
            closeOnOverlay: true,
            closeOnSave: true
        },

        /**
         * Initialize flyout system
         */
        init: function () {
            this.autoWire();
            FormHandler.init();
        },

        /**
         * Auto-wire triggers based on data attributes
         */
        autoWire: function () {
            const self = this;
            $(document).on('click', '[data-flyout-trigger]', function (e) {
                e.preventDefault();

                const $trigger = $(this);
                const flyoutId = $trigger.data('flyout-trigger');
                const action = $trigger.data('flyout-action') || 'load';
                const data = $trigger.data() || {};

                // Remove data attributes we don't want to send
                delete data.flyoutTrigger;
                delete data.flyoutAction;

                self.load(flyoutId, action, data);
            });
        },

        /**
         * Load a flyout via AJAX
         */
        load: function (flyoutId, action, data) {
            const self = this;
            data = data || {};

            // Get config from centralized localization
            let config = null;
            if (typeof wpFlyoutConfig !== 'undefined' && wpFlyoutConfig.flyouts && wpFlyoutConfig.flyouts[flyoutId]) {
                config = wpFlyoutConfig.flyouts[flyoutId];
            }

            // Fallback for development/testing
            if (!config) {
                const baseName = flyoutId.replace('-flyout', '');
                const prefix = 'demo_' + baseName;
                config = {
                    ajax: {
                        load_action: prefix + '_load',
                        save_action: prefix + '_save',
                        delete_action: prefix + '_delete',
                        nonce: data.nonce || ''
                    }
                };
            }

            const actionName = config.ajax[action + '_action'] || config.ajax.load_action;
            const nonce = config.ajax.nonce || data.nonce || '';
            delete data.nonce;

            this.showLoading();

            const ajaxData = {
                action: actionName,
                _wpnonce: nonce
            };

            $.extend(ajaxData, data);

            const ajaxUrl = (typeof wpFlyoutConfig !== 'undefined' && wpFlyoutConfig.ajaxUrl)
                ? wpFlyoutConfig.ajaxUrl
                : (window.ajaxurl || '/wp-admin/admin-ajax.php');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function (response) {
                    if (response.success) {
                        self.handleLoadSuccess(flyoutId, response.data);
                    } else {
                        self.showError(response.data || self.getLocalizedString('error'));
                    }
                },
                error: function (xhr) {
                    self.showError('Network error: ' + xhr.statusText);
                },
                complete: function () {
                    self.hideLoading();
                }
            });
        },

        /**
         * Get localized string
         */
        getLocalizedString: function (key, defaultValue) {
            if (typeof wpFlyoutConfig !== 'undefined' && wpFlyoutConfig.i18n && wpFlyoutConfig.i18n[key]) {
                return wpFlyoutConfig.i18n[key];
            }
            return defaultValue || key;
        },

        /**
         * Handle successful load
         */
        handleLoadSuccess: function (flyoutId, data) {
            // Remove any existing instance
            $('#' + flyoutId).remove();

            // Add the new flyout HTML
            $('body').append(data.html);

            // Open the flyout
            this.open(flyoutId);

            // Initialize components
            const $flyout = $('#' + flyoutId);
            this.wireForm($flyout, flyoutId);

            // Initialize components if they exist
            this.initializeComponents($flyout);
        },

        /**
         * Initialize components that are loaded
         */
        initializeComponents: function ($flyout) {
            // File Manager
            if (typeof window.WPFlyoutFileManager !== 'undefined') {
                WPFlyoutFileManager.initIn($flyout);
            }

            // Notes
            if (typeof window.WPFlyoutNotes !== 'undefined') {
                WPFlyoutNotes.initIn($flyout);
            }

            // Order Items
            if (typeof window.WPOrderItems !== 'undefined') {
                WPOrderItems.initIn($flyout);
            }
        },

        /**
         * Wire up form submission
         */
        wireForm: function ($flyout, flyoutId) {
            const self = this;
            const $form = $flyout.find('.wp-flyout-form');

            if (!$form.length) return;

            $form.off('submit.flyout').on('submit.flyout', function (e) {
                e.preventDefault();
                self.submitForm(flyoutId, $(this));
            });
        },

        /**
         * Submit form via AJAX
         */
        submitForm: function (flyoutId, $form) {
            const self = this;

            // Get config from centralized localization
            let config = null;
            if (typeof wpFlyoutConfig !== 'undefined' && wpFlyoutConfig.flyouts && wpFlyoutConfig.flyouts[flyoutId]) {
                config = wpFlyoutConfig.flyouts[flyoutId];
            }

            if (!config) {
                const baseName = flyoutId.replace('-flyout', '');
                const prefix = 'demo_' + baseName;
                config = {
                    ajax: {
                        save_action: prefix + '_save',
                        nonce: $form.find('[name="_wpnonce"]').val() || ''
                    }
                };
            }

            const saveAction = config.ajax.save_action;
            const nonce = config.ajax.nonce || $form.find('[name="_wpnonce"]').val() || '';

            const $submit = $form.find('[type="submit"]');
            const originalText = $submit.text();
            const loadingText = $submit.data('loading-text') || this.getLocalizedString('saving', 'Saving...');

            $form.find('.wp-flyout-form-notice').remove();

            if (!this.validateForm($form)) {
                return;
            }

            $submit.prop('disabled', true).text(loadingText);

            const formData = new FormData($form[0]);
            formData.append('action', saveAction);
            formData.append('_wpnonce', nonce);

            const ajaxUrl = (typeof wpFlyoutConfig !== 'undefined' && wpFlyoutConfig.ajaxUrl)
                ? wpFlyoutConfig.ajaxUrl
                : (window.ajaxurl || '/wp-admin/admin-ajax.php');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        self.handleSaveSuccess(flyoutId, response.data);
                    } else {
                        self.showFormError($form, response.data || 'Save failed');
                    }
                },
                error: function (xhr) {
                    self.showFormError($form, 'Network error: ' + xhr.statusText);
                },
                complete: function () {
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Handle successful save
         */
        handleSaveSuccess: function (flyoutId, data) {
            if (data.row_html) {
                const $existingRow = $('.wp-list-table tbody tr[data-id="' + data.id + '"]');

                if ($existingRow.length) {
                    const $newRow = $(data.row_html);
                    $existingRow.replaceWith($newRow);
                    $newRow.css('background', '#ffffcc');
                    setTimeout(function () {
                        $newRow.css('background', '');
                    }, 2000);
                } else {
                    const $newRow = $(data.row_html);
                    $('.wp-list-table tbody').prepend($newRow);
                    $newRow.css('background', '#d4edda');
                    setTimeout(function () {
                        $newRow.css('background', '');
                    }, 2000);
                }
            }

            this.showTableNotice(data.message || this.getLocalizedString('success', 'Saved successfully'), 'success');
            this.close(flyoutId);
        },

        /**
         * Open a flyout
         */
        open: function (id) {
            const self = this;
            const $flyout = $('#' + id);

            if (!$flyout.length) {
                console.error('Flyout not found:', id);
                return;
            }

            this.activeInstances[id] = {
                element: $flyout
            };

            if (!$('.wp-flyout-overlay').length) {
                $('body').append('<div class="wp-flyout-overlay"></div>');
            }

            const $overlay = $('.wp-flyout-overlay');
            $('body').addClass('wp-flyout-open');

            setTimeout(function () {
                $overlay.addClass('active');
                $flyout.addClass('active');
            }, 10);

            this.initTabs($flyout);
            this.bindCloseEvents($flyout, $overlay);

            setTimeout(function () {
                $flyout.find('input:visible, select:visible, textarea:visible, button:visible')
                    .not(':disabled')
                    .first()
                    .focus();
            }, 350);
        },

        /**
         * Close a flyout
         */
        close: function (id) {
            const self = this;
            const instance = this.activeInstances[id];
            if (!instance) return;

            const $flyout = instance.element;
            const $overlay = $('.wp-flyout-overlay');

            $flyout.removeClass('active');

            setTimeout(function () {
                $flyout.remove();

                if (Object.keys(self.activeInstances).length === 1) {
                    $overlay.removeClass('active');
                    $('body').removeClass('wp-flyout-open');

                    setTimeout(function () {
                        $overlay.remove();
                    }, 300);
                }

                delete self.activeInstances[id];
            }, 300);
        },

        /**
         * Close all flyouts
         */
        closeAll: function () {
            const self = this;
            Object.keys(this.activeInstances).forEach(function (id) {
                self.close(id);
            });
        },

        /**
         * Bind close events
         */
        bindCloseEvents: function ($flyout, $overlay) {
            const self = this;
            const id = $flyout.attr('id');

            $flyout.find('.wp-flyout-close').off('click.flyout').on('click.flyout', function (e) {
                e.preventDefault();
                self.close(id);
            });

            $flyout.find('.wp-flyout-cancel').off('click.flyout').on('click.flyout', function (e) {
                e.preventDefault();
                self.close(id);
            });

            $overlay.off('click.flyout-' + id).on('click.flyout-' + id, function () {
                self.close(id);
            });

            $(document).off('keydown.flyout-' + id).on('keydown.flyout-' + id, function (e) {
                if (e.key === 'Escape' && self.activeInstances[id]) {
                    self.close(id);
                }
            });
        },

        /**
         * Initialize tabs
         */
        initTabs: function ($flyout) {
            const $nav = $flyout.find('.wp-flyout-tab-nav');
            const $tabs = $nav.find('.wp-flyout-tab');
            const $contents = $flyout.find('.wp-flyout-tab-content');

            $tabs.off('click.tabs').on('click.tabs', function (e) {
                e.preventDefault();

                const $tab = $(this);
                if ($tab.hasClass('disabled')) return;

                const tabId = $tab.data('tab');

                $tabs.removeClass('active').attr('aria-selected', 'false');
                $tab.addClass('active').attr('aria-selected', 'true');

                $contents.removeClass('active');
                $flyout.find('#tab-' + tabId).addClass('active');
            });
        },

        /**
         * Validate form
         */
        validateForm: function ($form) {
            let valid = true;
            let firstError = null;

            $form.find('.error').removeClass('error');

            $form.find('[required]').each(function () {
                const $field = $(this);
                const value = $field.val();

                if (!value || (Array.isArray(value) && value.length === 0)) {
                    $field.addClass('error');
                    if (!firstError) firstError = $field;
                    valid = false;
                }
            });

            if (!valid) {
                if (firstError) {
                    firstError.focus();
                }
                const message = this.getLocalizedString('required', 'Please fill in all required fields');
                const $notice = $('<div class="wp-flyout-form-notice error">' + message + '</div>');
                $form.prepend($notice);
                setTimeout(function () {
                    $notice.fadeOut(function () {
                        $(this).remove();
                    });
                }, 3000);
            }

            return valid;
        },

        /**
         * Show form error
         */
        showFormError: function ($form, message) {
            const $notice = $('<div class="wp-flyout-form-notice error">' + message + '</div>');
            $form.prepend($notice);
            $form.get(0).scrollTop = 0;
        },

        /**
         * Show/hide loading
         */
        showLoading: function () {
            if (!$('#wp-flyout-loading').length) {
                $('body').append('<div id="wp-flyout-loading" class="wp-flyout-loading-overlay"><div class="wp-flyout-spinner"></div></div>');
            }
            $('#wp-flyout-loading').fadeIn(200);
        },

        hideLoading: function () {
            $('#wp-flyout-loading').fadeOut(200);
        },

        /**
         * Show table notice
         */
        showTableNotice: function (message, type) {
            type = type || 'info';

            const $notice = $(
                '<div class="notice notice-' + type + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>'
            );

            $('.wp-header-end').after($notice);

            $notice.find('.notice-dismiss').on('click', function () {
                $notice.fadeOut(function () {
                    $(this).remove();
                });
            });

            if (type === 'success') {
                setTimeout(function () {
                    $notice.fadeOut(function () {
                        $(this).remove();
                    });
                }, 5000);
            }
        },

        showError: function (message) {
            this.showTableNotice(message, 'error');
        }
    };

    /**
     * Form Handler Component (stays in core)
     */
    const FormHandler = {
        init: function () {
            $(document).on('input change', '.wp-flyout input.error, .wp-flyout select.error, .wp-flyout textarea.error', function () {
                $(this).removeClass('error');
            });

            $(document).on('click', '[data-confirm]', function (e) {
                const message = $(this).data('confirm');
                if (message && !confirm(message)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });
        }
    };

    /**
     * Public API
     */
    window.WPFlyout = {
        open: function (id) {
            return WPFlyout.open(id);
        },
        close: function (id) {
            return WPFlyout.close(id);
        },
        closeAll: function () {
            return WPFlyout.closeAll();
        },
        load: function (flyoutId, action, data) {
            return WPFlyout.load(flyoutId, action, data);
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyout.init();
    });

})(jQuery, window, document);