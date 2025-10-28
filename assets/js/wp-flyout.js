/**
 * WP Flyout Core JavaScript
 *
 * Handles flyout open/close mechanics and provides API for Manager integration
 * Supports both legacy (DOM-based) and new (dynamic) patterns
 *
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    /**
     * WP Flyout Core
     */
    window.WPFlyout = window.WPFlyout || {};

    // Default configuration
    WPFlyout.defaults = {
        width: 'medium',
        position: 'right',
        closeOnEscape: true,
        closeOnOverlay: true,
        animationDuration: 300,
        animationDelay: 10,
        focusDelay: 350
    };

    // Active flyouts tracking
    WPFlyout.active = [];
    WPFlyout.instances = {};

    /**
     * Open a flyout
     * Supports both legacy (ID-based) and new (options-based) patterns
     *
     * @param {string|Object} idOrOptions Flyout ID (legacy) or options object (new)
     */
    WPFlyout.open = function (idOrOptions) {
        // Legacy support: if string ID passed, use original behavior
        if (typeof idOrOptions === 'string') {
            return WPFlyout.openLegacy(idOrOptions);
        }

        // New pattern: options object
        const settings = $.extend({}, WPFlyout.defaults, idOrOptions);

        // Create unique ID if not provided
        const flyoutId = settings.id || 'flyout-' + Date.now();

        // Check if already exists in DOM (could be pre-rendered)
        let $flyout = $('#' + flyoutId);

        if ($flyout.length === 0) {
            // Create new flyout
            $flyout = WPFlyout.create(flyoutId, settings);
        } else {
            // Flyout exists in DOM (pre-rendered by PHP)
            // Just update it if needed
            if (settings.content) {
                WPFlyout.setContent($flyout, settings.content);
            }
            if (settings.title) {
                WPFlyout.setTitle($flyout, settings.title);
            }
            if (settings.width) {
                WPFlyout.setWidth($flyout, settings.width);
            }
        }

        // Show overlay
        WPFlyout.showOverlay();

        // Store instance
        WPFlyout.instances[flyoutId] = $flyout;

        // Add body class
        $('body').addClass('wp-flyout-open');

        // Add active class with delay for animation
        setTimeout(() => {
            $flyout.addClass('active');
        }, WPFlyout.defaults.animationDelay);

        // Track active flyout
        if (WPFlyout.active.indexOf(flyoutId) === -1) {
            WPFlyout.active.push(flyoutId);
        }

        // Initialize tabs
        WPFlyout.initTabs($flyout);

        // Bind events
        WPFlyout.bindEvents($flyout, settings);

        // Focus management and trigger events after animation
        setTimeout(() => {
            // Focus first input
            $flyout.find('input:visible, select:visible, textarea:visible')
                .not(':disabled')
                .first()
                .focus();

            // Trigger events
            $(document).trigger('wpflyout:opened', {
                element: $flyout[0],
                id: flyoutId,
                settings: settings
            });

            $flyout.trigger('flyout:ready');
        }, WPFlyout.defaults.focusDelay);

        return $flyout;
    };

    /**
     * Legacy open method - for flyouts already in DOM
     * This maintains backward compatibility with original behavior
     */
    WPFlyout.openLegacy = function (id) {
        const $flyout = $('#' + id);

        if (!$flyout.length) {
            console.warn('WP Flyout: Element not found with ID:', id);
            return false;
        }

        // Create overlay if needed
        WPFlyout.ensureOverlay();

        // Store instance
        WPFlyout.instances[id] = $flyout;

        // Add body class
        $('body').addClass('wp-flyout-open');

        // Activate with delay for animation
        setTimeout(() => {
            $('.wp-flyout-overlay').addClass('active');
            $flyout.addClass('active');
        }, WPFlyout.defaults.animationDelay);

        // Track active flyout
        if (WPFlyout.active.indexOf(id) === -1) {
            WPFlyout.active.push(id);
        }

        // Initialize tabs if present
        WPFlyout.initTabs($flyout);

        // Focus management and trigger events after animation
        setTimeout(() => {
            // Focus first input
            $flyout.find('input:visible, select:visible, textarea:visible')
                .not(':disabled')
                .first()
                .focus();

            // Trigger events
            const eventData = {
                id: id,
                element: $flyout[0]
            };

            $(document).trigger('wpflyout:opened', eventData);
            $flyout.trigger('flyout:ready');
        }, WPFlyout.defaults.focusDelay);

        return $flyout;
    };

    /**
     * Create flyout element dynamically
     */
    WPFlyout.create = function (id, settings) {
        const html = `
            <div id="${id}" class="wp-flyout wp-flyout-dynamic wp-flyout-${settings.position} wp-flyout-${settings.width}">
                <div class="wp-flyout-header">
                    <h2></h2>
                    <button type="button" class="wp-flyout-close" aria-label="Close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="wp-flyout-content">
                    <div class="wp-flyout-body"></div>
                </div>
                <div class="wp-flyout-footer" style="display: none;"></div>
            </div>
        `;

        return $(html).appendTo('body');
    };

    /**
     * Open flyout from HTML content
     */
    WPFlyout.openFromHTML = function (html, config) {
        const settings = $.extend({}, WPFlyout.defaults, config);

        // Parse the HTML to see if it's already a flyout structure
        const $temp = $('<div>').html(html);
        const $existingFlyout = $temp.find('.wp-flyout').first();

        if ($existingFlyout.length) {
            // Use existing flyout structure
            const flyoutId = $existingFlyout.attr('id') || 'flyout-' + Date.now();
            $existingFlyout.attr('id', flyoutId);

            // Remove any existing instance
            $('#' + flyoutId).remove();

            // Append to body
            $existingFlyout.appendTo('body');

            // Show overlay
            WPFlyout.showOverlay();

            // Add active class
            $existingFlyout.addClass('active');

            // Track active flyout
            WPFlyout.active.push(flyoutId);

            // Bind events
            WPFlyout.bindEvents($existingFlyout, settings);

            // Trigger opened event
            $(document).trigger('wpflyout:opened', {
                element: $existingFlyout[0],
                id: flyoutId,
                settings: settings
            });

            return $existingFlyout;
        } else {
            // Treat as content for new flyout
            return WPFlyout.open($.extend(settings, {
                content: html
            }));
        }
    };

    /**
     * Close a flyout
     * Supports both legacy (ID-based) and new patterns
     */
    WPFlyout.close = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);

        if (!$flyout.length) {
            // Try checking instances
            const instance = typeof flyout === 'string' ? WPFlyout.instances[flyout] : null;
            if (!instance) return false;
            return WPFlyout.close(instance);
        }

        const flyoutId = $flyout.attr('id');

        // Trigger closing event
        const event = $.Event('wpflyout:closing');
        $(document).trigger(event, {
            element: $flyout[0],
            id: flyoutId
        });

        if (event.isDefaultPrevented()) {
            return false;
        }

        // Start close animation
        $flyout.removeClass('active');

        // Remove from tracking
        WPFlyout.active = WPFlyout.active.filter(id => id !== flyoutId);
        delete WPFlyout.instances[flyoutId];

        // Clean up after animation
        setTimeout(() => {
            // For dynamically created flyouts, remove them
            if ($flyout.hasClass('wp-flyout-dynamic')) {
                $flyout.remove();
            }
            // For pre-rendered flyouts (legacy), just hide them
            // They stay in DOM for potential re-use

            // Remove overlay if no more flyouts
            if (WPFlyout.isEmpty()) {
                WPFlyout.removeOverlay();
            }

            // Trigger closed event
            $(document).trigger('wpflyout:closed', {id: flyoutId});
        }, WPFlyout.defaults.animationDuration);

        return true;
    };

    /**
     * Close all flyouts
     */
    WPFlyout.closeAll = function () {
        const flyouts = [...WPFlyout.active];
        flyouts.forEach(id => WPFlyout.close(id));
    };

    /**
     * Get the last opened flyout
     */
    WPFlyout.getLastId = function () {
        return WPFlyout.active.length ? WPFlyout.active[WPFlyout.active.length - 1] : null;
    };

    /**
     * Check if there are no active flyouts
     */
    WPFlyout.isEmpty = function () {
        return WPFlyout.active.length === 0;
    };

    /**
     * Ensure overlay exists
     */
    WPFlyout.ensureOverlay = function () {
        if (!$('.wp-flyout-overlay').length) {
            $('body').append('<div class="wp-flyout-overlay"></div>');
        }
    };

    /**
     * Remove overlay
     */
    WPFlyout.removeOverlay = function () {
        $('.wp-flyout-overlay').removeClass('active');
        $('body').removeClass('wp-flyout-open');

        setTimeout(() => {
            $('.wp-flyout-overlay').remove();
        }, WPFlyout.defaults.animationDuration);
    };

    /**
     * Set flyout content
     */
    WPFlyout.setContent = function ($flyout, content) {
        $flyout.find('.wp-flyout-body').html(content);
    };

    /**
     * Set flyout title
     */
    WPFlyout.setTitle = function ($flyout, title) {
        $flyout.find('.wp-flyout-header h2').text(title);
    };

    /**
     * Set flyout width
     */
    WPFlyout.setWidth = function ($flyout, width) {
        $flyout.removeClass('wp-flyout-small wp-flyout-medium wp-flyout-large wp-flyout-full')
            .addClass('wp-flyout-' + width);
    };

    /**
     * Show loading state
     */
    WPFlyout.showLoading = function (flyout, message) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        const loadingHtml = `
            <div class="wp-flyout-loading">
                <span class="spinner is-active"></span>
                <p>${message || 'Loading...'}</p>
            </div>
        `;

        WPFlyout.setContent($flyout, loadingHtml);
    };

    /**
     * Show error state
     */
    WPFlyout.showError = function (flyout, message) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        const errorHtml = `
            <div class="notice notice-error">
                <p>${message || 'An error occurred'}</p>
            </div>
        `;

        WPFlyout.setContent($flyout, errorHtml);
    };

    /**
     * Show message
     */
    WPFlyout.showMessage = function (flyout, message, type) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        const $content = $flyout.find('.wp-flyout-content');

        // Remove existing notices
        $content.find('.wp-flyout-notice').remove();

        // Add new notice
        const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        const $notice = $(`
            <div class="notice ${noticeClass} wp-flyout-notice is-dismissible">
                <p>${message}</p>
            </div>
        `).prependTo($content);

        // Auto dismiss success messages
        if (type === 'success') {
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 3000);
        }
    };

    /**
     * Bind form auto-submit
     */
    WPFlyout.bindFormAutoSubmit = function (flyout, handler) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);

        $flyout.find('form').off('submit.wpflyout').on('submit.wpflyout', function (e) {
            e.preventDefault();

            if (handler && typeof handler === 'function') {
                handler($(this));
            }

            // Trigger form submit event
            $(document).trigger('wpflyout:formsubmit', {
                element: $flyout[0],
                form: this
            });
        });
    };

    /**
     * Track dirty state
     */
    WPFlyout.trackDirty = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);

        $flyout.data('isDirty', false);

        $flyout.find('form :input').on('change.dirty input.dirty', function () {
            $flyout.data('isDirty', true);
        });

        return $flyout;
    };

    /**
     * Check if flyout has unsaved changes
     */
    WPFlyout.isDirty = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        return $flyout.data('isDirty') === true;
    };

    /**
     * Reset dirty state
     */
    WPFlyout.resetDirty = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        $flyout.data('isDirty', false);
    };

    /**
     * Show overlay
     */
    WPFlyout.showOverlay = function () {
        let $overlay = $('.wp-flyout-overlay');

        if ($overlay.length === 0) {
            $overlay = $('<div class="wp-flyout-overlay"></div>').appendTo('body');
        }

        // Small delay to ensure transition works
        setTimeout(function () {
            $overlay.addClass('active');
        }, 10);
    };

    /**
     * Hide overlay
     */
    WPFlyout.hideOverlay = function () {
        const $overlay = $('.wp-flyout-overlay');

        $overlay.removeClass('active');

        setTimeout(function () {
            $overlay.remove();
        }, WPFlyout.defaults.animationDuration);
    };

    /**
     * Hide overlay (alias for removeOverlay for backward compatibility)
     */
    WPFlyout.hideOverlay = function () {
        WPFlyout.removeOverlay();
    };

    /**
     * Initialize tab switching
     */
    WPFlyout.initTabs = function ($flyout) {
        $flyout.off('click.flyout-tabs').on('click.flyout-tabs', '.wp-flyout-tab', function (e) {
            e.preventDefault();

            const $tab = $(this);

            if ($tab.hasClass('disabled')) {
                return;
            }

            const tabId = $tab.data('tab');

            // Update active states
            $flyout.find('.wp-flyout-tab')
                .removeClass('active')
                .attr('aria-selected', 'false');

            $tab.addClass('active')
                .attr('aria-selected', 'true');

            // Switch content
            $flyout.find('.wp-flyout-tab-content').removeClass('active');
            $flyout.find('#tab-' + tabId).addClass('active');

            // Trigger event
            $(document).trigger('wpflyout:tab-changed', {
                flyoutId: $flyout.attr('id'),
                tabId: tabId
            });
        });
    };

    /**
     * Bind flyout events
     */
    WPFlyout.bindEvents = function ($flyout, settings) {
        // Tab navigation is handled in initTabs
        // Additional event bindings can go here if needed
    };

    /**
     * Bind tab navigation (legacy alias for initTabs)
     */
    WPFlyout.bindTabNavigation = function ($flyout) {
        WPFlyout.initTabs($flyout);
    };

    /**
     * Get active flyout
     */
    WPFlyout.getActive = function () {
        if (WPFlyout.active.length === 0) return null;
        return $('#' + WPFlyout.active[WPFlyout.active.length - 1]);
    };

    /**
     * Refresh element via AJAX
     */
    WPFlyout.refreshElement = function (selector) {
        const $element = $(selector);
        if (!$element.length) return;

        // Add loading state
        $element.css('opacity', '0.5');

        // Reload the page content
        $.get(window.location.href, function (html) {
            const $newElement = $(html).find(selector);
            if ($newElement.length) {
                $element.replaceWith($newElement);

                // Trigger refresh event
                $(document).trigger('wpflyout:refreshed', {
                    selector: selector
                });
            }
        }).always(function () {
            $element.css('opacity', '1');
        });
    };

    /**
     * Initialize flyout system
     */
    WPFlyout.init = function () {
        const self = this;

        // Bind global event handlers

        // Close button
        $(document).off('click.wpflyout-close').on('click.wpflyout-close', '.wp-flyout-close', function () {
            const $flyout = $(this).closest('.wp-flyout');
            const flyoutId = $flyout.attr('id');
            self.close(flyoutId);
        });

        // Overlay click
        $(document).off('click.wpflyout-overlay').on('click.wpflyout-overlay', '.wp-flyout-overlay', function () {
            const lastId = self.getLastId();
            if (lastId) {
                self.close(lastId);
            }
        });

        // Escape key
        $(document).off('keydown.wpflyout').on('keydown.wpflyout', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                const lastId = self.getLastId();
                if (lastId) {
                    self.close(lastId);
                }
            }
        });
    };

    /**
     * Initialize on ready
     */
    $(document).ready(function () {
        WPFlyout.init();

        // Clean up on page unload
        $(window).on('beforeunload', function () {
            if (WPFlyout.active.length > 0) {
                const $dirty = WPFlyout.active.map(id => $('#' + id))
                    .filter($f => WPFlyout.isDirty($f));

                if ($dirty.length > 0) {
                    return 'You have unsaved changes. Are you sure you want to leave?';
                }
            }
        });
    });

})(jQuery);